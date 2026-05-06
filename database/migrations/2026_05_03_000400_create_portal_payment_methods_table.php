<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('portal_payment_methods', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('portal_type', 64);
            $table->unsignedBigInteger('portal_id');
            $table->unsignedBigInteger('payment_method_id');
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();

            $table->unique(['portal_type', 'portal_id', 'payment_method_id'], 'portal_payment_methods_unique');
            $table->index(['portal_type', 'portal_id'], 'portal_payment_methods_portal_index');
            $table->foreign('payment_method_id', 'portal_payment_methods_method_foreign')
                ->references('id')
                ->on('payment_methods')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_payment_methods');
    }
};
