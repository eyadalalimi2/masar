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
        if (! Schema::hasTable('order_payments')) {
            Schema::create('order_payments', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('order_id');
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

                $table->index('order_id');
                $table->index('payment_method_id');
                $table->index('account_id');
                $table->index('status');
                $table->index('paid_at');

                $table->foreign('order_id', 'order_payments_order_id_fk')
                    ->references('id')
                    ->on('orders')
                    ->cascadeOnDelete();

                $table->foreign('payment_method_id', 'order_payments_payment_method_fk')
                    ->references('id')
                    ->on('payment_methods')
                    ->nullOnDelete();

                $table->foreign('account_id', 'order_payments_account_id_fk')
                    ->references('id')
                    ->on('accounts')
                    ->nullOnDelete();
            });
        }

        $this->migrateLegacyPaymentsTable();
        $this->migrateLegacyOrderInlineMetadata();
        $this->dropLegacyOrderColumns();
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'payment_method_name')) {
                $table->string('payment_method_name')->nullable()->after('payment_method_id');
            }
            if (! Schema::hasColumn('orders', 'payment_account_number')) {
                $table->string('payment_account_number')->nullable()->after('payment_method_name');
            }
            if (! Schema::hasColumn('orders', 'payment_account_name')) {
                $table->string('payment_account_name')->nullable()->after('payment_account_number');
            }
            if (! Schema::hasColumn('orders', 'payment_note')) {
                $table->text('payment_note')->nullable()->after('payment_account_name');
            }
        });

        if (Schema::hasTable('order_payments')) {
            Schema::dropIfExists('order_payments');
        }
    }

    private function migrateLegacyPaymentsTable(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasTable('orders')) {
            return;
        }

        DB::table('payments')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $order = DB::table('orders')
                        ->where('id', (int) $row->order_id)
                        ->first([
                            'id',
                            'buyer_type',
                            'buyer_id',
                            'payment_method_id',
                            'payment_method_name',
                            'payment_account_number',
                            'payment_account_name',
                            'payment_note',
                        ]);

                    if (! $order) {
                        continue;
                    }

                    $notes = $this->buildNotes(
                        $order->payment_account_name,
                        $order->payment_account_number,
                        $order->payment_note,
                        $row->notes
                    );

                    DB::table('order_payments')->insert([
                        'uuid' => (string) Str::uuid(),
                        'order_id' => (int) $order->id,
                        'payment_method_id' => $order->payment_method_id !== null ? (int) $order->payment_method_id : null,
                        'account_id' => $this->resolveAccountId($order),
                        'amount' => (float) ($row->amount ?? 0),
                        'currency' => 'YER',
                        'status' => (string) ($row->status ?? 'unpaid'),
                        'transaction_reference' => 'TYPE:' . strtolower((string) ($row->payment_type ?? 'credit')) . '|LEGACY_PAYMENT_ID:' . (int) $row->id,
                        'notes' => $notes,
                        'paid_at' => $row->paid_at,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ]);
                }
            });
    }

    private function migrateLegacyOrderInlineMetadata(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        DB::table('orders')
            ->where(function ($query): void {
                $query->whereNotNull('payment_method_name')
                    ->orWhereNotNull('payment_account_number')
                    ->orWhereNotNull('payment_account_name')
                    ->orWhereNotNull('payment_note');
            })
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('order_payments')
                    ->whereColumn('order_payments.order_id', 'orders.id');
            })
            ->orderBy('id')
            ->chunkById(200, function ($orders): void {
                foreach ($orders as $order) {
                    $notes = $this->buildNotes(
                        $order->payment_account_name,
                        $order->payment_account_number,
                        $order->payment_note,
                        null
                    );

                    DB::table('order_payments')->insert([
                        'uuid' => (string) Str::uuid(),
                        'order_id' => (int) $order->id,
                        'payment_method_id' => $order->payment_method_id !== null ? (int) $order->payment_method_id : null,
                        'account_id' => $this->resolveAccountId($order),
                        'amount' => 0,
                        'currency' => 'YER',
                        'status' => 'unpaid',
                        'transaction_reference' => 'TYPE:credit|LEGACY_ORDER_METADATA',
                        'notes' => $notes,
                        'paid_at' => null,
                        'created_at' => $order->created_at ?? now(),
                        'updated_at' => $order->updated_at ?? now(),
                    ]);
                }
            });
    }

    private function dropLegacyOrderColumns(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        if (Schema::hasColumn('orders', 'payment_method_name')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropColumn('payment_method_name');
            });
        }

        if (Schema::hasColumn('orders', 'payment_account_number')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropColumn('payment_account_number');
            });
        }

        if (Schema::hasColumn('orders', 'payment_account_name')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropColumn('payment_account_name');
            });
        }

        if (Schema::hasColumn('orders', 'payment_note')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropColumn('payment_note');
            });
        }
    }

    private function resolveAccountId(object $order): ?int
    {
        $buyerType = (string) ($order->buyer_type ?? '');
        $buyerId = (int) ($order->buyer_id ?? 0);

        if ($buyerId <= 0 || $buyerType === '') {
            return null;
        }

        $accountId = DB::table('accounts')
            ->where('owner_type', $buyerType)
            ->where('owner_id', $buyerId)
            ->value('id');

        return $accountId !== null ? (int) $accountId : null;
    }

    private function buildNotes(?string $accountName, ?string $accountNumber, ?string $legacyOrderNote, ?string $legacyPaymentNote): ?string
    {
        $parts = [];

        $accountName = trim((string) $accountName);
        $accountNumber = trim((string) $accountNumber);
        $legacyOrderNote = trim((string) $legacyOrderNote);
        $legacyPaymentNote = trim((string) $legacyPaymentNote);

        if ($accountName !== '') {
            $parts[] = 'اسم الحساب: ' . $accountName;
        }

        if ($accountNumber !== '') {
            $parts[] = 'رقم الحساب: ' . $accountNumber;
        }

        if ($legacyOrderNote !== '') {
            $parts[] = 'ملاحظة الطلب: ' . $legacyOrderNote;
        }

        if ($legacyPaymentNote !== '') {
            $parts[] = 'ملاحظة الدفع: ' . $legacyPaymentNote;
        }

        return $parts === [] ? null : implode("\n", $parts);
    }
};
