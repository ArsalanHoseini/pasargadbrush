<?php

namespace App\Console\Commands\Wordpress;

use App\Models\BlogPost;
use App\Services\WordPressImport\PostBodyRepair;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Imported articles kept WordPress's raw body: «[caption]» shortcodes shown as
 * text, and images pointing at the WordPress copy they were imported from
 * («http://localhost/pasargadbrush/wp-content/uploads/…», broken anywhere
 * else). This copies those images into the media library and rewrites the
 * bodies (see PostBodyRepair). The article's updated_at is kept — its text
 * didn't change. Idempotent; dry run unless --commit.
 */
#[Signature('wp:repair-post-bodies
    {--commit : Actually write changes; default is a dry run}
    {--uploads= : The WordPress wp-content/uploads folder to copy images from (default: WP_UPLOADS_PATH)}')]
#[Description('Copy images used inside imported articles into the media library and expand [caption] shortcodes')]
class RepairPostBodies extends Command
{
    public function handle(PostBodyRepair $repair): int
    {
        $commit = (bool) $this->option('commit');
        $uploadsPath = (string) ($this->option('uploads') ?: config('wordpress_import.uploads_path'));

        if ($uploadsPath === '' || ! is_dir($uploadsPath)) {
            $this->error("WordPress uploads folder not found: «{$uploadsPath}». Pass --uploads= or set WP_UPLOADS_PATH.");

            return self::FAILURE;
        }

        $posts = BlogPost::query()->get()->filter(fn (BlogPost $post) => PostBodyRepair::needsRepair((string) $post->body));
        $copied = [];
        $missing = [];

        foreach ($posts as $post) {
            $result = $repair->repair((string) $post->body, $uploadsPath, $commit, $post->title);
            $copied = [...$copied, ...$result['copied']];
            $missing = [...$missing, ...$result['missing']];

            $this->line(sprintf('%s  «%s»: %d image(s)%s', $commit ? 'fixed' : 'would fix', $post->title, count($result['copied']), $result['missing'] ? ', '.count($result['missing']).' missing' : ''));

            if ($commit && $result['body'] !== $post->body) {
                $post->body = $result['body'];
                $post->timestamps = false;
                $post->save();
            }
        }

        foreach (array_unique($missing) as $file) {
            $this->warn("Not in the uploads folder, left as is: {$file}");
        }

        $this->info(sprintf(
            '%d article(s), %d distinct image file(s)%s, %d missing.%s',
            $posts->count(),
            count(array_unique($copied)),
            $commit ? ' copied' : ' to copy',
            count(array_unique($missing)),
            $commit ? '' : ' Dry run — re-run with --commit to apply.',
        ));

        return self::SUCCESS;
    }
}
