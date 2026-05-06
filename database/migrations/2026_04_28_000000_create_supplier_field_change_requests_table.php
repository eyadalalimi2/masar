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
        Schema::create('supplier_field_change_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('supplier_id')->index('supplier_field_change_requests_supplier_id_foreign');
            $table->unsignedBigInteger('requested_by_user_id')->index('supplier_field_change_requests_requested_by_user_id_foreign');
            $table->string('field_key', 100);
            $table->text('requested_value');
            $table->text('note')->nullable();
            $table->string('document_path')->nullable();
            $table->string('status', 64)->default('pending');
            $table->unsignedBigInteger('reviewed_by_user_id')->nullable()->index('supplier_field_change_requests_reviewed_by_user_id_foreign');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_field_change_requests');
    }
};
