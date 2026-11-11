<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('core.audit.view');

        $validated = $request->validate([
            'event' => ['nullable', Rule::in(['created', 'updated', 'deleted'])],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'model' => ['nullable', Rule::in([User::class, AppSetting::class])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = AuditLog::query()->with('actor');

        if (filled($validated['event'] ?? null)) {
            $query->where('event', $validated['event']);
        }

        if (filled($validated['user_id'] ?? null)) {
            $query->where('user_id', $validated['user_id']);
        }

        if (filled($validated['model'] ?? null)) {
            $query->where('auditable_type', $validated['model']);
        }

        if (filled($validated['from'] ?? null)) {
            $query->whereDate('created_at', '>=', $validated['from']);
        }

        if (filled($validated['to'] ?? null)) {
            $query->whereDate('created_at', '<=', $validated['to']);
        }

        return view('core.audit-logs.index', [
            'auditLogs' => $query->latest()->paginate(25)->withQueryString(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'models' => [User::class => 'Pengguna', AppSetting::class => 'Pengaturan'],
            'filters' => $validated,
        ]);
    }
}
