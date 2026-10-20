<?php

namespace Tests\Feature\WordPressImport;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Builds a minimal in-memory SQLite stand-in for the "wordpress" connection,
 * with just the columns our importers actually query — so these tests don't
 * depend on the real local WordPress copy being present on the machine.
 */
trait WithFakeWordPressDatabase
{
    protected function setUpFakeWordPressDatabase(): void
    {
        Config::set('database.connections.wordpress', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        DB::purge('wordpress');
        $schema = Schema::connection('wordpress');

        $schema->create('terms', function ($table) {
            $table->id('term_id');
            $table->string('name');
            $table->string('slug');
        });

        $schema->create('term_taxonomy', function ($table) {
            $table->id('term_taxonomy_id');
            $table->unsignedBigInteger('term_id');
            $table->string('taxonomy');
            $table->unsignedBigInteger('parent')->default(0);
            $table->text('description')->nullable();
        });

        $schema->create('term_relationships', function ($table) {
            $table->unsignedBigInteger('object_id');
            $table->unsignedBigInteger('term_taxonomy_id');
        });

        $schema->create('icl_translations', function ($table) {
            $table->id();
            $table->unsignedBigInteger('element_id');
            $table->string('element_type');
            $table->string('language_code');
        });

        $schema->create('posts', function ($table) {
            $table->id('ID');
            $table->string('post_title');
            $table->string('post_name');
            $table->text('post_excerpt')->nullable();
            $table->longText('post_content')->nullable();
            $table->string('post_status');
            $table->dateTime('post_date');
        });

        $schema->create('postmeta', function ($table) {
            $table->id('meta_id');
            $table->unsignedBigInteger('post_id');
            $table->string('meta_key');
            $table->text('meta_value')->nullable();
        });
    }

    protected function wp(string $table)
    {
        return DB::connection('wordpress')->table($table);
    }
}
