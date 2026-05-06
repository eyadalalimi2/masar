<?php

use App\Models\Customer\Consumer;
use App\Models\Customer\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('buyer_type', 120)->nullable()->after('distributor_id')->index();
            $table->unsignedBigInteger('buyer_id')->nullable()->after('buyer_type')->index();
        });

        DB::transaction(function (): void {
            DB::table('orders')
                ->where('customer_type', 'b2b')
                ->whereNotNull('customer_id')
                ->update([
                    'buyer_type' => Customer::class,
                    'buyer_id' => DB::raw('customer_id'),
                ]);

            DB::table('orders')
                ->where('customer_type', 'b2c')
                ->whereNotNull('consumer_id')
                ->update([
                    'buyer_type' => Consumer::class,
                    'buyer_id' => DB::raw('consumer_id'),
                ]);
        });

        try {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropForeign('orders_customer_id_foreign');
            });
        } catch (Throwable) {
            // Foreign key may already be missing in some environments.
        }

        try {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropForeign('orders_consumer_id_foreign');
            });
        } catch (Throwable) {
            // Foreign key may already be missing in some environments.
        }

        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'customer_id')) {
                $table->dropColumn('customer_id');
            }

            if (Schema::hasColumn('orders', 'consumer_id')) {
                $table->dropColumn('consumer_id');
            }

            if (Schema::hasColumn('orders', 'customer_type')) {
                $table->dropColumn('customer_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('customer_type', 64)->nullable()->after('distributor_id');
            $table->unsignedBigInteger('customer_id')->nullable()->after('customer_type')->index('orders_customer_id_foreign');
            $table->unsignedBigInteger('consumer_id')->nullable()->after('customer_id')->index('orders_consumer_id_foreign');
        });

        DB::transaction(function (): void {
            DB::table('orders')
                ->where('buyer_type', Customer::class)
                ->whereNotNull('buyer_id')
                ->update([
                    'customer_type' => 'b2b',
                    'customer_id' => DB::raw('buyer_id'),
                ]);

            DB::table('orders')
                ->where('buyer_type', Consumer::class)
                ->whereNotNull('buyer_id')
                ->update([
                    'customer_type' => 'b2c',
                    'consumer_id' => DB::raw('buyer_id'),
                ]);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreign('customer_id', 'orders_customer_id_foreign')
                ->references('id')
                ->on('customers')
                ->onUpdate('restrict')
                ->onDelete('set null');

            $table->foreign('consumer_id', 'orders_consumer_id_foreign')
                ->references('id')
                ->on('consumers')
                ->onUpdate('restrict')
                ->onDelete('set null');

            $table->dropColumn('buyer_id');
            $table->dropColumn('buyer_type');
        });
    }
};
