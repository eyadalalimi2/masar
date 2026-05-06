<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemAuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'min:1'],
            'event_type' => ['nullable', 'string', 'max:80'],
            'table_name' => ['nullable', 'string', 'max:120'],
            'record_id' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 20);

        $logs = AuditLog::query()
            ->forUser(isset($validated['user_id']) ? (int) $validated['user_id'] : null)
            ->forEvent($validated['event_type'] ?? null)
            ->forTable($validated['table_name'] ?? null)
            ->forRecord(isset($validated['record_id']) ? (int) $validated['record_id'] : null)
            ->betweenDates($validated['from'] ?? null, $validated['to'] ?? null)
            ->search($validated['search'] ?? null)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'تم جلب سجلات التدقيق بنجاح.',
            'filters' => [
                'user_id' => $validated['user_id'] ?? null,
                'event_type' => $validated['event_type'] ?? null,
                'table_name' => $validated['table_name'] ?? null,
                'record_id' => $validated['record_id'] ?? null,
                'from' => $validated['from'] ?? null,
                'to' => $validated['to'] ?? null,
                'search' => $validated['search'] ?? null,
                'per_page' => $perPage,
            ],
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ],
        ]);
    }
}
