<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CommentController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');

        $comments = Comment::query()
            ->with('post')
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.comments.index', [
            'comments' => $comments,
            'status' => $status,
        ]);
    }

    public function approve(Comment $comment): RedirectResponse
    {
        $comment->update(['status' => 'approved']);

        return back()->with('status', 'دیدگاه تأیید شد.');
    }

    public function spam(Comment $comment): RedirectResponse
    {
        $comment->update(['status' => 'spam']);

        return back()->with('status', 'دیدگاه به عنوان اسپم علامت‌گذاری شد.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $comment->delete();

        return back()->with('status', 'دیدگاه حذف شد.');
    }

    /**
     * Approve, mark as spam or delete several comments at once.
     */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'action' => ['required', Rule::in(['approve', 'spam', 'delete'])],
        ], ['ids.required' => 'هیچ موردی انتخاب نشده است.']);

        $selected = Comment::query()->whereKey($data['ids']);

        $count = match ($data['action']) {
            'approve' => $selected->update(['status' => 'approved']),
            'spam' => $selected->update(['status' => 'spam']),
            'delete' => $selected->delete(),
        };

        return back()->with('status', $count.' دیدگاه '.match ($data['action']) {
            'approve' => 'تأیید شد.',
            'spam' => 'به عنوان اسپم علامت‌گذاری شد.',
            'delete' => 'حذف شد.',
        });
    }
}
