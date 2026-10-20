<?php

namespace App\Console\Commands\Wordpress;

use App\Services\WordPressImport\BlogCategoryImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('wp:import-blog-categories {--commit : Actually write changes; default is a dry run}')]
#[Description('Import Persian blog categories from the local WordPress copy')]
class ImportBlogCategories extends Command
{
    public function handle(BlogCategoryImporter $importer): int
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
