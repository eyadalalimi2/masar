<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('workshop_order_payments')) {
            Schema::create('workshop_order_payments', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('purchase_order_id');
                $table->unsignedBigInteger('payment_method_id')->nullable();
                $table->unsignedBigInteger('account_id')->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('currency', 8)->default('YER');
                $table->string('status', 64)->default('unpaid');
                $table->string('transaction_reference')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('purchase_order_id');
                $table->index('payment_method_id');
                $table->index('account_id');
                $table->index('status');
                $table->index('paid_at');

                $table->foreign('purchase_order_id', 'workshop_order_payments_purchase_order_fk')
                    ->references('id')
                    ->on('workshop_purchase_orders')
                    ->cascadeOnDelete();

                $table->foreign('payment_method_id', 'workshop_order_payments_method_fk')
                    ->references('id')
                    ->on('payment_methods')
                    ->nullOnDelete();

                $table->foreign('account_id', 'workshop_order_payments_account_fk')
                    ->references('id')
                    ->on('accounts')
                    ->nullOnDelete();
            });
        }

        $this->migrateLegacyWorkshopOrderMetadata();
        $this->dropLegacyWorkshopOrderColumns();
    }

    public function down(): void
    {
        if (Schema::hasTable('workshop_purchase_orders')) {
            Schema::table('workshop_purchase_orders', function (Blueprint $table): void {
                if (! Schema::hasColumn('workshop_purchase_orders', 'payment_method_name')) {
                    $table->string('payment_method_name')->nullable()->after('payment_method_id');
                }
                if (! Schema::hasColumn('workshop_purchase_orders', 'payment_account_number')) {
                    $table->string('payment_account_number')->nullable()->after('payment_method_name');
                }
                if (! Schema::hasColumn('workshop_purchase_orders', 'payment_account_name')) {
                    $table->string('payment_account_name')->nullable()->after('payment_account_number');
                }
                if (! Schema::hasColumn('workshop_purchase_orders', 'payment_note')) {
                    $table->text('payment_note')->nullable()->after('payment_account_name');
                }
            });
        }

        if (Schema::hasTable('workshop_order_payments')) {
            Schema::dropIfExists('workshop_order_payments');
        }
    }

    private function migrateLegacyWorkshopOrderMetadata(): void
    {
        if (! Schema::hasTable('workshop_purchase_orders')) {
            return;
        }

        DB::table('workshop_purchase_orders')
            ->where(function ($query): void {
                $query->whereNotNull('payment_method_name')
                    ->orWhereNotNull('payment_account_number')
                    ->orWhereNotNull('payment_account_name')
                    ->orWhereNotNull('payment_note');
            })
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('workshop_order_payments')
                    ->whereColumn('workshop_order_payments.purchase_order_id', 'workshop_purchase_orders.id');
            })
            ->orderBy('id')
            ->chunkById(200, function ($orders): void {
                foreach ($orders as $order) {
                    $notes = $this->buildNotes(
                        $order->payment_account_name,
                        $order->payment_account_number,
                        $order->payment_note
                    );

                    DB::table('workshop_order_payments')->insert([
                        'uuid' => (string) Str::uuid(),
                        'purchase_order_id' => (int) $order->id,
                        'payment_method_id' => $order->payment_method_id !== null ? (int) $order->payment_method_id : null,
                        'account_id' => $this->resolveAccountId($order),
                        'amount' => (float) ($order->total_amount ?? 0),
                        'currency' => 'YER',
                        'status' => 'unpaid',
                        'transaction_reference' => 'TYPE:credit|LEGACY_WORKSHOP_ORDER_METADATA',
                        'notes' => $notes,
                        'paid_at' => null,
                        'created_at' => $order->created_at ?? now(),
                        'updated_at' => $order->updated_at ?? now(),
                    ]);
                }
            });
    }

    private function resolveAccountId(object $order): ?int
    {
        $workshopId = (int) ($order->workshop_id ?? 0);

        if ($workshopId <= 0) {
            return null;
        }

        $accountId = DB::table('accounts')
            ->where('owner_type', 'workshop')
            ->where('owner_id', $workshopId)
            ->value('id');

        return $accountId !== null ? (int) $accountId : null;
    }

    private function buildNotes(?string $accountName, ?string $accountNumber, ?string $legacyOrderNote): ?string
    {
        $parts = [];

        $accountName = trim((string) $accountName);
        $accountNumber = trim((string) $accountNumber);
        $legacyOrderNote = trim((string) $legacyOrderNote);

        if ($accountName !== '') {
            $parts[] = 'اسم الحساب: ' . $accountName;
        }

        if ($accountNumber !== '') {
            $parts[] = 'رقم الحساب: ' . $accountNumber;
        }

        if ($legacyOrderNote !== '') {
            $parts[] = 'ملاحظة الطلب: ' . $legacyOrderNote;
        }

        return $parts === [] ? null : implode("\n", $parts);
    }

    private function dropLegacyWorkshopOrderColumns(): void
    {
        if (! Schema::hasTable('workshop_purchase_orders')) {
            return;
        }

        if (Schema::hasColumn('workshop_purchase_orders', 'payment_method_name')) {
            Schema::table('workshop_purchase_orders', function (Blueprint $table): void {
                $table->dropColumn('payment_method_name');
            });
        }

        if (Schema::hasColumn('workshop_purchase_orders', 'payment_account_number')) {
            Schema::table('workshop_purchase_orders', function (Blueprint $table): void {
                $table->dropColumn('payment_account_number');
            });
        }

        if (Schema::hasColumn('workshop_purchase_orders', 'payment_account_name')) {
            Schema::table('workshop_purchase_orders', function (Blueprint $table): void {
                $table->dropColumn('payment_account_name');
            });
        }

        if (Schema::hasColumn('workshop_purchase_orders', 'payment_note')) {
            Schema::table('workshop_purchase_orders', function (Blueprint $table): void {
                $table->dropColumn('payment_note');
            });
        }
    }
};
