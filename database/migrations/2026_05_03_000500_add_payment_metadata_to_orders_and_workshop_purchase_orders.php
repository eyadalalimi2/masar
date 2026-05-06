<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('payment_method_id')->nullable()->after('distributor_stage');
            $table->string('payment_method_name')->nullable()->after('payment_method_id');
            $table->string('payment_account_number')->nullable()->after('payment_method_name');
            $table->string('payment_account_name')->nullable()->after('payment_account_number');
            $table->text('payment_note')->nullable()->after('payment_account_name');

            $table->foreign('payment_method_id', 'orders_payment_method_foreign')
                ->references('id')
                ->on('payment_methods')
                ->nullOnDelete();
        });

        Schema::table('workshop_purchase_orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('payment_method_id')->nullable()->after('status');
            $table->string('payment_method_name')->nullable()->after('payment_method_id');
            $table->string('payment_account_number')->nullable()->after('payment_method_name');
            $table->string('payment_account_name')->nullable()->after('payment_account_number');
            $table->text('payment_note')->nullable()->after('payment_account_name');

            $table->foreign('payment_method_id', 'workshop_po_payment_method_foreign')
                ->references('id')
                ->on('payment_methods')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign('orders_payment_method_foreign');
            $table->dropColumn([
                'payment_method_id',
                'payment_method_name',
                'payment_account_number',
                'payment_account_name',
                'payment_note',
            ]);
        });

        Schema::table('workshop_purchase_orders', function (Blueprint $table): void {
            $table->dropForeign('workshop_po_payment_method_foreign');
            $table->dropColumn([
                'payment_method_id',
                'payment_method_name',
                'payment_account_number',
                'payment_account_name',
                'payment_note',
            ]);
        });
    }
};
