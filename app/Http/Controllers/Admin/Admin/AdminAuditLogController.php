<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Admin\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::query()
            ->with('admin:id,name,phone')
            ->when($request->filled('admin_id'), function ($query) use ($request) {
                $query->where('admin_id', (int) $request->input('admin_id'));
            })
            ->when($request->filled('action'), function ($query) use ($request) {
                $query->where('action', 'like', '%' . $request->string('action')->toString() . '%');
            })
            ->when($request->filled('method'), function ($query) use ($request) {
                $query->where('method', $request->string('method')->toString());
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $admins = Admin::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.audit-logs.index', compact('logs', 'admins'));
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('admin:id,name,phone');

        return view('admin.audit-logs.show', compact('auditLog'));
    }
}
