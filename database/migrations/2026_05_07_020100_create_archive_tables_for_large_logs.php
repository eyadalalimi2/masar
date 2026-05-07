<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs_archive')) {
            Schema::create('audit_logs_archive', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('source_id')->unique();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('event_type', 80)->index();
                $table->string('table_name', 120)->index();
                $table->unsignedBigInteger('record_id')->nullable()->index();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 1024)->nullable();
                $table->string('device', 120)->nullable()->index();
                $table->timestamp('created_at')->nullable()->index();
                $table->timestamp('archived_at')->useCurrent()->index();

                $table->index(['table_name', 'record_id', 'created_at'], 'audit_logs_archive_table_record_created_idx');
                $table->index(['event_type', 'created_at'], 'audit_logs_archive_event_created_idx');
                $table->index(['user_id', 'event_type', 'created_at'], 'audit_logs_archive_user_event_created_idx');
            });
        }

        if (! Schema::hasTable('inventory_movements_archive')) {
            Schema::create('inventory_movements_archive', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('source_id')->unique();
                $table->unsignedBigInteger('supplier_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('product_unit_id')->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('agent_id')->nullable()->index();
                $table->string('movement_type', 64)->index();
                $table->decimal('quantity', 14, 3);
                $table->decimal('stock_before', 14, 3);
                $table->decimal('stock_after', 14, 3);
                $table->text('note')->nullable();
                $table->timestamp('created_at')->nullable()->index();
                $table->timestamp('updated_at')->nullable();
                $table->timestamp('archived_at')->useCurrent()->index();

                $table->index(['product_unit_id', 'created_at'], 'inventory_moves_archive_unit_created_idx');
                $table->index(['supplier_id', 'movement_type'], 'inventory_moves_archive_supplier_type_idx');
                $table->index(['branch_id', 'movement_type', 'id'], 'inventory_moves_archive_branch_type_id_idx');
                $table->index(['supplier_id', 'created_at'], 'inventory_moves_archive_supplier_created_idx');
            });
        }

        if (! Schema::hasTable('order_status_histories_archive')) {
            Schema::create('order_status_histories_archive', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('source_id')->unique();
                $table->unsignedBigInteger('order_id')->index();
                $table->string('from_status', 50)->index();
                $table->string('to_status', 50)->index();
                $table->string('actor_guard', 50)->default('system')->index();
                $table->unsignedBigInteger('actor_id')->nullable()->index();
                $table->text('note')->nullable();
                $table->timestamp('created_at')->nullable()->index();
                $table->timestamp('updated_at')->nullable();
                $table->timestamp('deleted_at')->nullable()->index();
                $table->timestamp('archived_at')->useCurrent()->index();

                $table->index(['order_id', 'created_at'], 'order_status_hist_archive_order_created_idx');
            });
        }

        if (! Schema::hasTable('distributor_location_logs_archive')) {
            Schema::create('distributor_location_logs_archive', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('source_id')->unique();
                $table->unsignedBigInteger('distributor_id')->index();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->decimal('accuracy_meters', 14, 3)->nullable();
                $table->text('note')->nullable();
                $table->timestamp('created_at')->nullable()->index();
                $table->timestamp('updated_at')->nullable();
                $table->timestamp('deleted_at')->nullable()->index();
                $table->timestamp('archived_at')->useCurrent()->index();

                $table->index(['distributor_id', 'created_at'], 'dist_location_archive_dist_created_idx');
                $table->index(['order_id', 'created_at'], 'dist_location_archive_order_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('distributor_location_logs_archive');
        Schema::dropIfExists('order_status_histories_archive');
        Schema::dropIfExists('inventory_movements_archive');
        Schema::dropIfExists('audit_logs_archive');
    }
};
