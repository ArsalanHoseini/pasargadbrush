<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WordPressImport\BlogCategoryImporter;
use App\Services\WordPressImport\BlogImporter;
use App\Services\WordPressImport\ImportResult;
use App\Services\WordPressImport\ProductCategoryImporter;
use App\Services\WordPressImport\ProductImporter;
use App\Support\PersianDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WordpressImportController extends Controller
{
    private const STEPS = [
        'product-categories' => ['label' => 'دسته‌بندی محصولات', 'importer' => ProductCategoryImporter::class],
        'products' => ['label' => 'محصولات', 'importer' => ProductImporter::class],
        'blog-categories' => ['label' => 'دسته‌بندی بلاگ', 'importer' => BlogCategoryImporter::class],
        'blog-posts' => ['label' => 'مقالات بلاگ', 'importer' => BlogImporter::class],
    ];

    public function index(): View
    {
        return view('admin.import.index', [
            'steps' => self::STEPS,
            'lastResults' => session('wp_import_results', []),
        ]);
    }

    public function run(Request $request, string $step): RedirectResponse
    {
        if (! array_key_exists($step, self::STEPS)) {
            abort(404);
        }

        $commit = $request->boolean('commit');

        /** @var ImportResult $result */
        $result = app(self::STEPS[$step]['importer'])->run($commit);

        $results = session('wp_import_results', []);
        $results[$step] = [
            'commit' => $commit,
            'imported' => $result->imported,
            'skipped' => $result->skipped,
            'failed' => $result->failed,
            'messages' => $result->messages,
            'ran_at' => persian_date(now(), PersianDate::COMPACT),
        ];

        return redirect()->route('admin.import.index')
            ->with('wp_import_results', $results)
            ->with('status', ($commit ? 'ایمپورت' : 'پیش‌نمایش').' «'.self::STEPS[$step]['label'].'» اجرا شد.');
    }
}
