<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class SearchController extends Controller
{
    /** Too common to narrow a search. */
    private const STOP_WORDS = ['و', 'با', 'در', 'از', 'به', 'برای', 'های', 'ها'];

    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $words = $this->words($query);

        $products = $words === []
            ? new LengthAwarePaginator([], 0, 12)
            : $this->search($words, matchAll: true) ?? $this->search($words, matchAll: false);

        return view('search.index', [
            'products' => $products->withQueryString(),
            'query' => $query,
        ]);
    }

    /**
     * Products matching every word (or, when $matchAll is false, any word)
     * in their name or descriptions. Returns null for an empty all-words
     * result so the caller can fall back to any-word.
     */
    private function search(array $words, bool $matchAll): ?LengthAwarePaginator
    {
        $results = Product::query()
            ->with(['images', 'primaryCategory'])
            ->where('is_active', true)
            ->where(function (Builder $query) use ($words, $matchAll) {
                foreach ($words as $word) {
                    $clause = fn (Builder $wordQuery) => $this->matchWord($wordQuery, $word);
                    $matchAll ? $query->where($clause) : $query->orWhere($clause);
                }
            })
            ->orderBy('name')
            ->paginate(12);

        return $matchAll && $results->total() === 0 && count($words) > 1 ? null : $results;
    }

    /**
     * Old content mixes Arabic «ي/ك» with Persian «ی/ک»; match either.
     */
    private function matchWord(Builder $query, string $word): void
    {
        $variants = array_unique([
            $word,
            strtr($word, ['ی' => 'ي', 'ک' => 'ك']),
            strtr($word, ['ي' => 'ی', 'ك' => 'ک']),
        ]);

        foreach ($variants as $variant) {
            foreach (['name', 'short_description', 'description'] as $column) {
                $query->orWhere($column, 'like', '%'.$variant.'%');
            }
        }
    }

    /**
     * @return list<string>
     */
    private function words(string $query): array
    {
        $words = preg_split('/[\s\-‌]+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(
            $words,
            fn (string $word) => mb_strlen($word) > 1 && ! in_array($word, self::STOP_WORDS, true),
        )) ?: ($query !== '' ? [$query] : []);
    }
}
