<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Society;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The full audit-log browser (Settings-adjacent, super-admin-only). This is
 * what the Super Admin dashboard's "Recent Activities" card used to show a
 * short preview of — that preview was removed from the dashboard in favor
 * of this dedicated, filterable, paginated page.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::with(['superAdmin', 'society'])->latest();

        if ($action = $request->string('action')->trim()->value()) {
            $query->byAction($action);
        }

        if ($module = $request->string('module')->trim()->value()) {
            $query->byModule($module);
        }

        if ($societyId = $request->string('society_id')->trim()->value()) {
            $query->bySociety($societyId);
        }

        if ($from = $request->string('from')->trim()->value()) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->string('to')->trim()->value()) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->paginate(25)->withQueryString();

        $actions = AuditLog::query()->distinct()->orderBy('action')->pluck('action');
        $modules = AuditLog::query()->distinct()->orderBy('module')->pluck('module');
        $societies = Society::orderBy('name')->get(['id', 'name']);

        return view('admin.audit_logs.index', compact('logs', 'actions', 'modules', 'societies'));
    }
}
