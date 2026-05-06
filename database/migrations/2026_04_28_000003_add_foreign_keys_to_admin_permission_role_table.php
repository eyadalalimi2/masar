<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admin_permission_role', function (Blueprint $table) {
            $table->foreign(['permission_id'])->references(['id'])->on('admin_permissions')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['role_id'])->references(['id'])->on('admin_roles')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_permission_role', function (Blueprint $table) {
            $table->dropForeign('admin_permission_role_permission_id_foreign');
            $table->dropForeign('admin_permission_role_role_id_foreign');
        });
    }
};
