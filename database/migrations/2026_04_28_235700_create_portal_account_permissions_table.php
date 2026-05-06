<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_account_permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('guard_name', 32);
            $table->unsignedBigInteger('account_id');
            $table->string('permission', 120);
            $table->boolean('is_granted')->default(true);
            $table->timestamps();

            $table->unique(['guard_name', 'account_id', 'permission'], 'pap_guard_account_permission_unique');
            $table->index(['guard_name', 'account_id'], 'pap_guard_account_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_account_permissions');
    }
};
