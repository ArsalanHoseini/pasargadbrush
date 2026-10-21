<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));

        $products = $query === ''
            ? new LengthAwarePaginator([], 0, 12)
            : Product::query()
                ->with('images')
                ->where('is_active', true)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', '%'.$query.'%')
                        ->orWhere('short_description', 'like', '%'.$query.'%')
                        ->orWhere('description', 'like', '%'.$query.'%');
                })
                ->orderBy('name')
                ->paginate(12)
                ->withQueryString();

        return view('search.index', [
            'products' => $products,
            'query' => $query,
        ]);
    }
}
