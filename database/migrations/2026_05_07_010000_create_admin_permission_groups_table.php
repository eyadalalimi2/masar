<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_permission_groups')) {
            Schema::create('admin_permission_groups', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('group_key', 64)->unique();
                $table->string('name', 120);
                $table->unsignedSmallInteger('display_order')->default(0)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        $this->seedDefaultGroups();
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_permission_groups');
    }

    private function seedDefaultGroups(): void
    {
        if (! Schema::hasTable('admin_permission_groups')) {
            return;
        }

        $labels = (array) config('operations.security.permission_group_labels', []);
        $order = array_values((array) config('operations.security.permission_group_order', []));
        $orderLookup = [];

        foreach ($order as $index => $groupKey) {
            if (is_string($groupKey) && trim($groupKey) !== '') {
                $orderLookup[$groupKey] = $index + 1;
            }
        }

        $existingKeys = collect();
        if (Schema::hasTable('admin_permissions') && Schema::hasColumn('admin_permissions', 'group_key')) {
            $existingKeys = DB::table('admin_permissions')
                ->select('group_key')
                ->distinct()
                ->pluck('group_key')
                ->filter(fn($value) => is_string($value) && trim($value) !== '')
                ->map(fn($value) => trim((string) $value));
        }

        $allKeys = $existingKeys
            ->merge(array_keys($labels))
            ->filter(fn($value) => is_string($value) && trim($value) !== '')
            ->unique()
            ->values();

        foreach ($allKeys as $groupKey) {
            $normalized = (string) $groupKey;
            DB::table('admin_permission_groups')->updateOrInsert(
                ['group_key' => $normalized],
                [
                    'name' => (string) ($labels[$normalized] ?? str_replace('_', ' ', $normalized)),
                    'display_order' => (int) ($orderLookup[$normalized] ?? 999),
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
};
