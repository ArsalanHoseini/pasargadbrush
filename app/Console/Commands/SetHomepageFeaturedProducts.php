<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Owner-curated list of products to show on the homepage (requested
 * 2026-10-24 — see docs/DECISIONS.md for how each name was matched to a
 * real SKU, including the three ambiguous names the owner resolved).
 *
 * Idempotent: re-running just re-applies the same is_featured/sort_order
 * values, and un-features anything previously featured but no longer on
 * this list.
 */
#[Signature('wp:set-homepage-featured-products {--commit : Actually write changes; default is a dry run}')]
#[Description('Mark the owner-curated list of products as featured on the homepage, in order')]
class SetHomepageFeaturedProducts extends Command
{
    /**
     * Ordered list of SKU => Persian name (as the owner named it), for
     * logging clarity. Order here is the homepage display order.
     *
     * @var array<string, string>
     */
    private const FEATURED_SKUS = [
        'WP-30868' => 'برس اسب',
        'WP-30801' => 'برس اسپیندل',
        'WP-25147' => 'برس جارو برقی',
        'WP-25055' => 'برس چمن مصنوعی',
        'WP-25145' => 'برس درزگیر',
        'WP-25085' => 'برس دستگاه واکس برقی',
        'WP-25119' => 'برس سنباده چوب',
        'WP-25081' => 'برس شستشوی پنل خورشیدی',
        'WP-25049' => 'برس صنایع شیشه',
        'WP-25115' => 'برس طناب پاک کن',
        'WP-25077' => 'برس قالیشویی',
        'WP-25070' => 'برس قشو گاو',
        'WP-25110' => 'برس مبل شو',
        'WP-25129' => 'برس هویج شویی',
        'WP-25135' => 'برس سورتینگ مرکبات',
        'WP-25132' => 'برس سیب زمینی شو',
        // The owner's newest articles (imported 2026-11-07 by wp:import-unregistered-products) —
        // 8 more keep the grid full at 2, 3 and 4 columns (24 cards).
        'WP-30850' => 'انواع برس سیلندری',
        'WP-30842' => 'انواع برس دیسکی',
        'WP-30818' => 'فرچه قالیشویی',
        'WP-30821' => 'فرچه شستشو سیب زمینی',
        'WP-30783' => 'فرچه سورتینگ',
        'WP-30991' => 'دستگاه برس چمن مصنوعی',
        'WP-31086' => 'فرچه و برس نساجی',
        'WP-30710' => 'برس سیلندری',
    ];

    public function handle(): int
    {
        $commit = $this->option('commit');

        if (! $commit) {
            $this->info('[DRY RUN] — pass --commit to actually write changes.');
        }

        $position = 0;

        foreach (self::FEATURED_SKUS as $sku => $label) {
            $product = Product::where('sku', $sku)->first();

            if (! $product) {
                $this->warn("Skipped: no product with SKU {$sku} ({$label})");

                continue;
            }

            $this->line(($commit ? 'Featuring' : '[DRY RUN] Would feature')." «{$product->name}» ({$sku}) at position {$position}");

            if ($commit) {
                $product->update(['is_featured' => true, 'sort_order' => $position]);
            }

            $position++;
        }

        $unfeatured = Product::where('is_featured', true)->whereNotIn('sku', array_keys(self::FEATURED_SKUS))->get();

        foreach ($unfeatured as $product) {
            $this->line(($commit ? 'Un-featuring' : '[DRY RUN] Would un-feature')." «{$product->name}» ({$product->sku}) — no longer on the curated list");

            if ($commit) {
                $product->update(['is_featured' => false]);
            }
        }

        return self::SUCCESS;
    }
}
