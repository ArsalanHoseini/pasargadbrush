<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Admin\ActivityDescriptions;
use App\Support\PersianDate;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Who did what in the dashboard, from audit_logs (admins only).
 */
class ActivityLogController extends Controller
{
    public function index(Request $request, ActivityDescriptions $descriptions): View
    {
        $from = PersianDate::parse($request->query('from'));
        $to = PersianDate::parse($request->query('to'));
        $prefixes = ActivityDescriptions::GROUPS[$request->query('group')][1] ?? null;

        $logs = AuditLog::query()
            ->with(['user', 'auditable'])
            ->when($request->filled('user'), fn ($query) => $query->where('user_id', $request->integer('user')))
            ->when($prefixes, fn ($query) => $query->where(function ($query) use ($prefixes) {
                foreach ($prefixes as $prefix) {
                    $query->orWhere('action', 'like', $prefix.'%');
                }
            }))
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<', $to->modify('+1 day')))
            ->latest()
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.activity.index', [
            'logs' => $logs,
            'descriptions' => $descriptions,
            'userOptions' => User::orderBy('name')->pluck('name', 'id')->all(),
            'groupOptions' => collect(ActivityDescriptions::GROUPS)->map(fn (array $group) => $group[0])->all(),
        ]);
    }
}
