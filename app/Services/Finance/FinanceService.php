<?php

namespace App\Services\Finance;

use App\Models\Finance\CustomerAccount;
use App\Models\Finance\Payment;
use App\Models\Orders\Order as CustomerOrder;
use App\Models\Orders\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinanceService
{
    public function createPayment(Order $order, array $data): Payment
    {
        return DB::transaction(function () use ($order, $data) {
            $customerId = $order->buyer_type === CustomerOrder::BUYER_TYPE_CUSTOMER ? (int) $order->buyer_id : null;
            $customerName = trim((string) ($order->customer?->name ?? $order->customer_name ?? ''));
            $account = $customerId
                ? CustomerAccount::firstOrCreate(
                    ['owner_id' => $customerId],
                    [
                        'name' => $customerName !== '' ? $customerName : 'عميل',
                        'balance' => 0,
                    ]
                )
                : null;

            $previousPaid = (float) $order->payments()->sum('amount');
            $remainingBefore = max(0, (float) $order->total_price - $previousPaid);
            $existingCount = (int) $order->payments()->count();

            if ($remainingBefore <= 0) {
                abort(422, 'الطلب مسدد بالكامل.');
            }

            $paymentType = $data['payment_type'];
            $requestedAmount = isset($data['amount']) ? (float) $data['amount'] : 0;
            $paymentAmount = 0.0;
            $status = 'unpaid';
            $paidAt = null;

            if ($paymentType === 'cash') {
                $paymentAmount = $remainingBefore;
                $status = 'paid';
                $paidAt = now();
            } else {
                $paymentAmount = min(max($requestedAmount, 0), $remainingBefore);
                $totalPaid = $previousPaid + $paymentAmount;

                if ($totalPaid <= 0) {
                    $status = 'unpaid';
                } elseif ($totalPaid < (float) $order->total_price) {
                    $status = 'partial';
                } else {
                    $status = 'paid';
                }

                if ($paymentAmount > 0) {
                    $paidAt = now();
                }
            }

            $payment = Payment::create([
                'uuid' => (string) Str::uuid(),
                'order_id' => $order->id,
                'payment_method_id' => $order->payment_method_id,
                'account_id' => $account?->id,
                'amount' => $paymentAmount,
                'currency' => (string) ($account?->currency ?: 'YER'),
                'status' => $status,
                'transaction_reference' => 'TYPE:' . strtolower($paymentType) . '|ORDER:' . $order->id . '|TX:' . Str::upper(Str::random(10)),
                'paid_at' => $paidAt,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($paymentType === 'credit') {
                if ($existingCount === 0) {
                    $this->handleCredit($order);
                }

                if ($paymentAmount > 0 && $account) {
                    $this->updateCustomerBalance($account, -$paymentAmount);
                    $this->recordTransaction($account, 'credit', $paymentAmount, 'تحصيل دفعة من الطلب #' . $order->id);
                }
            }

            if ($paymentType === 'cash' && $existingCount > 0 && $paymentAmount > 0 && $account) {
                $this->updateCustomerBalance($account, -$paymentAmount);
                $this->recordTransaction($account, 'credit', $paymentAmount, 'تحصيل نقدي للطلب #' . $order->id);
            }

            return $payment->fresh(['order', 'supplier', 'distributor']);
        });
    }

    public function updateCustomerBalance(CustomerAccount $customer, float $amount): CustomerAccount
    {
        $newBalance = (float) $customer->balance + $amount;
        if ($newBalance < 0) {
            $newBalance = 0;
        }

        $customer->update(['balance' => $newBalance]);

        return $customer->fresh();
    }

    public function recordTransaction(CustomerAccount $customer, string $type, float $amount, string $description = ''): void
    {
        $customer->transactions()->create([
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
        ]);
    }

    public function handleCredit(Order $order): void
    {
        if ($order->buyer_type !== CustomerOrder::BUYER_TYPE_CUSTOMER || ! $order->buyer_id) {
            return;
        }

        $account = CustomerAccount::firstOrCreate(
            ['owner_id' => $order->buyer_id],
            [
                'name' => trim((string) ($order->customer?->name ?? $order->customer_name ?? '')) ?: 'عميل',
                'balance' => 0,
            ]
        );

        $paid = (float) $order->payments()->sum('amount');
        $debt = max(0, (float) $order->total_price - $paid);

        if ($debt > 0) {
            $this->updateCustomerBalance($account, $debt);
            $this->recordTransaction($account, 'debit', $debt, 'تسجيل دين للطلب #' . $order->id);
        }
    }
}
