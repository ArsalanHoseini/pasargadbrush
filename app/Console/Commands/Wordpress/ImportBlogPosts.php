<?php

namespace App\Console\Commands\Wordpress;

use App\Services\WordPressImport\BlogImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('wp:import-blog-posts {--commit : Actually write changes; default is a dry run}')]
#[Description('Import published Persian blog posts from the local WordPress copy (requires blog categories imported first)')]
class ImportBlogPosts extends Command
{
    public function handle(BlogImporter $importer): int
    {
        $commit = $this->option('commit');
        $result = $importer->run($commit);

        foreach ($result->messages as $message) {
            $this->line($message);
        }

        $this->newLine();
        $this->info(($commit ? 'Imported' : '[DRY RUN] Would import').": {$result->imported}, skipped: {$result->skipped}, failed: {$result->failed}");

        return self::SUCCESS;
    }
}
