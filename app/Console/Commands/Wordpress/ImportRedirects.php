<?php

namespace App\Console\Commands\Wordpress;

use App\Models\SeoRedirect;
use App\Services\WordPressImport\LegacyRedirectBuilder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('wp:import-redirects
    {--commit : Actually write changes; default is a dry run}
    {--csv= : Also write the full old→new URL list to this CSV file, for review}')]
#[Description('Generate 301/410 redirects for every old WordPress URL from the local WordPress copy')]
class ImportRedirects extends Command
{
    public function handle(LegacyRedirectBuilder $builder): int
    {
        $commit = (bool) $this->option('commit');
        $redirects = $builder->build();

        $manual = SeoRedirect::query()
            ->where('source', '!=', SeoRedirect::SOURCE_WORDPRESS)
            ->whereIn('from_path', array_keys($redirects))
            ->pluck('from_path')
            ->flip();

        $toWrite = array_diff_key($redirects, $manual->all());
        $stale = SeoRedirect::query()
            ->where('source', SeoRedirect::SOURCE_WORDPRESS)
            ->whereNotIn('from_path', array_keys($redirects))
            ->count();

        foreach (array_slice($toWrite, 0, 25, true) as $from => $redirect) {
            $this->line("{$redirect['code']}  {$from}  →  ".($redirect['to'] ?? '(410 gone)'));
        }
        if (count($toWrite) > 25) {
            $this->line('… '.(count($toWrite) - 25).' more (use --csv to see them all)');
        }

        if ($csvPath = $this->option('csv')) {
            $this->writeCsv($csvPath, $redirects);
            $this->info("Full list written to {$csvPath}");
        }

        if ($commit) {
            DB::transaction(function () use ($toWrite) {
                foreach ($toWrite as $from => $redirect) {
                    SeoRedirect::query()->updateOrCreate(['from_path' => $from], [
                        'to_path' => $redirect['to'],
                        'status_code' => $redirect['code'],
                        'source' => SeoRedirect::SOURCE_WORDPRESS,
                        'notes' => $redirect['note'],
                        'is_active' => true,
                    ]);
                }

                SeoRedirect::query()
                    ->where('source', SeoRedirect::SOURCE_WORDPRESS)
                    ->whereNotIn('from_path', array_keys($toWrite))
                    ->delete();
            });
        }

        $this->newLine();
        $this->info(($commit ? 'Saved' : '[DRY RUN] Would save').': '.count($toWrite).' redirects'
            .' ('.collect($toWrite)->where('code', 410)->count().' as 410 gone)'
            .', kept '.$manual->count().' staff-edited ones untouched'
            .', '.($commit ? 'removed' : 'would remove').' '.$stale.' stale generated ones.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, array{to: ?string, code: int, note: string}>  $redirects
     */
    private function writeCsv(string $path, array $redirects): void
    {
        $handle = fopen($path, 'w');
        fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows Persian correctly.
        fputcsv($handle, ['old_url', 'new_url', 'status', 'source'], ',', '"', '');

        foreach ($redirects as $from => $redirect) {
            fputcsv($handle, [$from, $redirect['to'] ?? '', $redirect['code'], $redirect['note']], ',', '"', '');
        }

        fclose($handle);
    }
}
