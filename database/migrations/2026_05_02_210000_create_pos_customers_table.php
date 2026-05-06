<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_customers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pos_account_id')->index();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['pos_account_id', 'phone'], 'pos_customers_pos_phone_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_customers');
    }
};
