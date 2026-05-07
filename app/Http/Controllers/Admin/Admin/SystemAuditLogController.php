<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit\AuditLog;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
            'include_archived' => ['nullable', 'boolean'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 20);

        $logs = $this->shouldUseArchiveAwareQuery($validated)
            ? $this->archiveAwarePaginate($validated, $perPage)
            : AuditLog::query()
            ->forUser(isset($validated['user_id']) ? (int) $validated['user_id'] : null)
            ->forEvent($validated['event_type'] ?? null)
            ->forTable($validated['table_name'] ?? null)
            ->forRecord(isset($validated['record_id']) ? (int) $validated['record_id'] : null)
            ->betweenDates($validated['from'] ?? null, $validated['to'] ?? null)
            ->search($validated['search'] ?? null)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $items = $logs->items();
        if ($logs instanceof LengthAwarePaginator && $logs->firstItem() !== null && isset($items[0]) && ! ($items[0] instanceof AuditLog)) {
            $items = $this->normalizeArchiveRows(collect($items))->all();
        }

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
                'include_archived' => (bool) ($validated['include_archived'] ?? false),
            ],
            'data' => $items,
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

    /**
     * @param array<string, mixed> $validated
     */
    private function shouldUseArchiveAwareQuery(array $validated): bool
    {
        if (! Schema::hasTable('audit_logs_archive')) {
            return false;
        }

        if ((bool) ($validated['include_archived'] ?? false)) {
            return true;
        }

        if (! isset($validated['from']) || ! is_string($validated['from']) || trim($validated['from']) === '') {
            return false;
        }

        $retentionDays = (int) config('archive.retention_days.audit_logs', 90);
        $cutoff = CarbonImmutable::now()->subDays(max(1, $retentionDays));

        try {
            $from = CarbonImmutable::parse($validated['from']);
        } catch (\Throwable) {
            return false;
        }

        return $from->lessThan($cutoff);
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function archiveAwarePaginate(array $validated, int $perPage): LengthAwarePaginator
    {
        $live = DB::table('audit_logs')->select([
            'id',
            'user_id',
            'event_type',
            'table_name',
            'record_id',
            'old_values',
            'new_values',
            'ip_address',
            'user_agent',
            'device',
            'created_at',
            DB::raw('0 as is_archived'),
        ]);

        $archive = DB::table('audit_logs_archive')->select([
            DB::raw('source_id as id'),
            'user_id',
            'event_type',
            'table_name',
            'record_id',
            'old_values',
            'new_values',
            'ip_address',
            'user_agent',
            'device',
            'created_at',
            DB::raw('1 as is_archived'),
        ]);

        $query = DB::query()->fromSub($live->unionAll($archive), 'audit_logs_all');

        if (isset($validated['user_id'])) {
            $query->where('user_id', (int) $validated['user_id']);
        }

        if (isset($validated['event_type']) && is_string($validated['event_type']) && trim($validated['event_type']) !== '') {
            $query->where('event_type', trim($validated['event_type']));
        }

        if (isset($validated['table_name']) && is_string($validated['table_name']) && trim($validated['table_name']) !== '') {
            $query->where('table_name', trim($validated['table_name']));
        }

        if (isset($validated['record_id'])) {
            $query->where('record_id', (int) $validated['record_id']);
        }

        if (isset($validated['from']) && is_string($validated['from']) && trim($validated['from']) !== '') {
            $query->where('created_at', '>=', trim($validated['from']));
        }

        if (isset($validated['to']) && is_string($validated['to']) && trim($validated['to']) !== '') {
            $query->where('created_at', '<=', trim($validated['to']));
        }

        if (isset($validated['search']) && is_string($validated['search']) && trim($validated['search']) !== '') {
            $term = trim($validated['search']);
            $query->where(function ($inner) use ($term): void {
                $inner->where('event_type', 'like', '%' . $term . '%')
                    ->orWhere('table_name', 'like', '%' . $term . '%')
                    ->orWhere('device', 'like', '%' . $term . '%')
                    ->orWhere('ip_address', 'like', '%' . $term . '%');
            });
        }

        return $query
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param Collection<int, object> $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function normalizeArchiveRows(Collection $rows): Collection
    {
        return $rows->map(function (object $row): array {
            $oldValues = $this->decodeJsonField($row->old_values ?? null);
            $newValues = $this->decodeJsonField($row->new_values ?? null);

            return [
                'id' => isset($row->id) ? (int) $row->id : null,
                'user_id' => isset($row->user_id) ? (int) $row->user_id : null,
                'event_type' => (string) ($row->event_type ?? ''),
                'table_name' => (string) ($row->table_name ?? ''),
                'record_id' => isset($row->record_id) ? (int) $row->record_id : null,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $row->ip_address,
                'user_agent' => $row->user_agent,
                'device' => $row->device,
                'created_at' => $row->created_at,
                'is_archived' => ((int) ($row->is_archived ?? 0)) === 1,
            ];
        })->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonField(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
