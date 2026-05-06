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
        Schema::table('admin_role_admin', function (Blueprint $table) {
            $table->foreign(['admin_id'])->references(['id'])->on('admins')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['role_id'])->references(['id'])->on('admin_roles')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_role_admin', function (Blueprint $table) {
            $table->dropForeign('admin_role_admin_admin_id_foreign');
            $table->dropForeign('admin_role_admin_role_id_foreign');
        });
    }
};
