<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\BlogPost;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, BlogPost $post): RedirectResponse
    {
        $post->comments()->create([
            ...$request->safe()->except('website'),
            'status' => 'pending',
        ]);

        return back()->with('comment_sent', true);
    }
}
