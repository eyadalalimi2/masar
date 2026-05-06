<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Status is string-based now; keep migration as no-op for forward compatibility.
    }

    public function down(): void
    {
        // Intentionally no-op.
    }
};
