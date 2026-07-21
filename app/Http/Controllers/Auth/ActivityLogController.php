<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Disaster\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ActivityLogController extends Controller
{
    public function index(): View
    {
        return view('activity-logs.index', [
            'page_title' => 'Activity Logs',
            'page_description' => 'Review account activity across the disaster management system.',
        ]);
    }

    public function data(): JsonResponse
    {
        $logs = AuditLog::query()
            ->with('user.roles:id,name')
            ->select(['audit_logs.id', 'audit_logs.uuid', 'audit_logs.user_id', 'audit_logs.auditable_type', 'audit_logs.auditable_id', 'audit_logs.action', 'audit_logs.old_values', 'audit_logs.new_values', 'audit_logs.ip_address', 'audit_logs.user_agent', 'audit_logs.created_at']);

        return DataTables::eloquent($logs)
            ->addColumn('account', fn (AuditLog $log) => $log->user?->name ?? 'Deleted account')
            ->addColumn('email', fn (AuditLog $log) => $log->user?->email ?? '—')
            ->addColumn('roles', fn (AuditLog $log) => $log->user?->getRoleNames()->map(fn ($role) => Str::headline($role))->join(', ') ?: '—')
            ->editColumn('action', fn (AuditLog $log) => Str::headline(str_replace('.', ' ', $log->action)))
            ->addColumn('module', fn (AuditLog $log) => $this->module($log))
            ->addColumn('result', fn (AuditLog $log) => $this->resultBadge($log))
            ->editColumn('created_at', fn (AuditLog $log) => $log->created_at?->format('M d, Y h:i:s A'))
            ->addColumn('details', fn (AuditLog $log) => $this->detailsButton($log))
            ->filterColumn('account', fn ($query, string $keyword) => $query->whereHas('user', fn ($user) => $user->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%")))
            ->filterColumn('roles', fn ($query, string $keyword) => $query->whereHas('user.roles', fn ($role) => $role->where('name', 'like', "%{$keyword}%")))
            ->rawColumns(['result', 'details'])
            ->toJson();
    }

    private function module(AuditLog $log): string
    {
        $route = $log->new_values['route'] ?? null;
        if ($route) {
            return Str::headline(explode('.', $route)[0]);
        }

        return Str::headline(class_basename($log->auditable_type));
    }

    private function resultBadge(AuditLog $log): string
    {
        $status = (int) ($log->new_values['status_code'] ?? 200);
        $tone = $status >= 400 ? 'danger' : 'success';

        return '<span class="badge badge-light-'.$tone.'">'.($status >= 400 ? 'Failed' : 'Success').' ('.$status.')</span>';
    }

    private function detailsButton(AuditLog $log): string
    {
        $payload = e(json_encode([
            'date' => $log->created_at?->format('M d, Y h:i:s A'),
            'account' => $log->user?->name ?? 'Deleted account',
            'email' => $log->user?->email,
            'roles' => $log->user?->getRoleNames()->map(fn ($role) => Str::headline($role))->values(),
            'action' => Str::headline(str_replace('.', ' ', $log->action)),
            'module' => $this->module($log),
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'old_values' => $log->old_values,
            'new_values' => $log->new_values,
        ], JSON_THROW_ON_ERROR));

        return '<button type="button" class="btn btn-sm btn-light-primary js-view-log" data-log="'.$payload.'">View</button>';
    }
}
