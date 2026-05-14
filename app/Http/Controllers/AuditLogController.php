<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $auditLogs = AuditLog::query()
            ->with('actor')
            ->when($request->filled('actor_id'), fn (Builder $query) => $query->where('actor_id', $request->integer('actor_id')))
            ->when($request->filled('action'), fn (Builder $query) => $query->where('action', (string) $request->string('action')))
            ->when($request->filled('subject'), function (Builder $query) use ($request): void {
                $query->where('event', (string) $request->string('subject'));
            })
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('audit-logs.index', [
            'auditLogs' => $auditLogs,
            'actors' => User::orderBy('name')->get(['id', 'name']),
            'actions' => AuditLog::query()
                ->select('action')
                ->whereNotNull('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
            'subjects' => AuditLog::query()
                ->select('event')
                ->whereNotNull('event')
                ->distinct()
                ->orderBy('event')
                ->pluck('event'),
            'filters' => $request->only(['actor_id', 'action', 'subject']),
        ]);
    }
}
