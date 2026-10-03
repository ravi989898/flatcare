<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\RoleDefinition;
use App\Models\Society;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only trail of every change a society's own Admin has made under
 * Settings -> Permissions in the society portal (Society\
 * PermissionSettingController). One row per form save - each row's old/new
 * values span every role/menu item that save touched, keyed
 * "{role} → {menu item}" - rather than one row per role, so Super Admin
 * sees exactly what a single "Save Permissions" click changed. A filtered
 * view of the same audit_logs table as Admin\AuditLogController, scoped to
 * module 'permission_management'.
 */
class PermissionLogController extends Controller
{
    private const MANAGED_ROLES = ['treasurer', 'vice_chairman', 'secretary', 'committee_member', 'resident'];

    public function index(Request $request): View
    {
        $query = AuditLog::with('society')
            ->where('module', 'permission_management')
            ->latest();

        if ($societyId = $request->string('society_id')->trim()->value()) {
            $query->bySociety((int) $societyId);
        }

        if ($roleName = $request->string('role')->trim()->value()) {
            $displayName = RoleDefinition::where('name', $roleName)->value('display_name');

            if ($displayName) {
                $query->whereRaw('CAST(new_values AS CHAR) LIKE ?', ["%{$displayName} \u{2192}%"]);
            }
        }

        if ($from = $request->string('from')->trim()->value()) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->string('to')->trim()->value()) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->paginate(25)->withQueryString();

        $societies = Society::orderBy('name')->get(['id', 'name']);
        $roles = RoleDefinition::whereIn('name', self::MANAGED_ROLES)->orderByDesc('priority')->get(['name', 'display_name']);

        return view('admin.permission_logs.index', compact('logs', 'societies', 'roles'));
    }
}
