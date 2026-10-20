<?php

namespace App\Services\WordPressImport;

use App\Models\BlogPost;
use App\Models\MigrationMapping;
use Illuminate\Support\Facades\DB;

/**
 * Imports published Persian ("fa") blog posts from the local WordPress
 * copy. Read-only against the "wordpress" connection; idempotent via
 * migration_mappings. Requires blog categories to have been imported
 * first (BlogCategoryImporter). Does not import the ~3,500 stale draft
 * posts found in the source (post_status != 'publish' is excluded).
 */
class BlogImporter
{
    public function __construct(private MediaCopier $mediaCopier) {}

    public function run(bool $commit): ImportResult
    {
        $result = new ImportResult;

        $posts = DB::connection('wordpress')
            ->table('posts as p')
            ->join('icl_translations as it', function ($join) {
                $join->on('it.element_id', '=', 'p.ID')
                    ->where('it.element_type', '=', 'post_post');
            })
            ->where('it.language_code', 'fa')
            ->where('p.post_status', 'publish')
            ->select('p.ID', 'p.post_title', 'p.post_name', 'p.post_content', 'p.post_date')
            ->get();

        foreach ($posts as $post) {
            $this->importPost($post, $commit, $result);
        }

        return $result;
    }

    private function importPost(object $wpPost, bool $commit, ImportResult $result): void
    {
        $existing = MigrationMapping::query()
            ->where('source_system', 'wordpress')
            ->where('source_type', 'post')
            ->where('source_id', $wpPost->ID)
            ->first();

        if ($existing && $existing->target_id) {
            $result->skipped("مقاله «{$wpPost->post_title}» قبلاً وارد شده بود.");

            return;
        }

        $slug = urldecode($wpPost->post_name);
        $categoryId = $this->mapToLocalCategoryId($wpPost->ID);

        if (! $commit) {
            $result->imported("[پیش‌نمایش] مقاله: «{$wpPost->post_title}» (slug: {$slug})");

            return;
        }

        $post = BlogPost::create([
            'blog_category_id' => $categoryId,
            'title' => $wpPost->post_title,
            'slug' => $slug,
            'body' => $wpPost->post_content,
            'status' => 'published',
            'published_at' => $wpPost->post_date,
        ]);

        $this->attachFeaturedImage($post, $wpPost->ID, $commit);

        MigrationMapping::updateOrCreate(
            ['source_system' => 'wordpress', 'source_type' => 'post', 'source_id' => $wpPost->ID],
            [
                'target_type' => BlogPost::class,
                'target_id' => $post->id,
                'status' => 'imported',
                'source_url' => "https://pasargadbrush.com/{$slug}/",
                'target_url' => "/blog/{$slug}",
            ],
        );

        $result->imported("مقاله «{$wpPost->post_title}» وارد شد.");
    }

    private function mapToLocalCategoryId(int $wpPostId): ?int
    {
        $termTaxonomyIds = DB::connection('wordpress')
            ->table('term_relationships as tr')
            ->join('term_taxonomy as tt', 'tt.term_taxonomy_id', '=', 'tr.term_taxonomy_id')
            ->where('tr.object_id', $wpPostId)
            ->where('tt.taxonomy', 'category')
            ->pluck('tt.term_taxonomy_id');

        if ($termTaxonomyIds->isEmpty()) {
            return null;
        }

        return MigrationMapping::query()
            ->where('source_system', 'wordpress')
            ->where('source_type', 'category')
            ->whereIn('source_id', $termTaxonomyIds)
            ->whereNotNull('target_id')
            ->value('target_id');
    }

    private function attachFeaturedImage(BlogPost $post, int $wpPostId, bool $commit): void
    {
        $thumbnailId = DB::connection('wordpress')->table('postmeta')
            ->where('post_id', $wpPostId)->where('meta_key', '_thumbnail_id')
            ->value('meta_value');

        if (! $thumbnailId) {
            return;
        }

        $media = $this->mediaCopier->copy((int) $thumbnailId, $commit, $post->title);

        if ($media) {
            $post->featuredImage()->attach($media->id, ['role' => 'featured', 'sort_order' => 0]);
        }
    }
}
