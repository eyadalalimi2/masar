<?php

namespace App\Services\Pos;

use App\Models\Customer\Customer;
use App\Models\Pos;
use Illuminate\Support\Facades\Auth;

class PosContextService
{
    public function currentPos(): Pos
    {
        $pos = Auth::guard('pos')->user();

        if (! $pos instanceof Pos) {
            abort(403);
        }

        return $pos;
    }

    public function resolveOrCreateCustomer(Pos $pos): Customer
    {
        $customer = null;

        if ((int) ($pos->customer_id ?? 0) > 0) {
            $customer = Customer::query()->whereKey((int) $pos->customer_id)->first();
        }

        if (! $customer) {
            $customer = Customer::query()->firstOrCreate(
                [
                    'phone' => $pos->phone,
                ],
                [
                    'type' => 'retail_store',
                    'name' => $pos->name,
                    'whatsapp' => $pos->whatsapp,
                    'address' => $pos->address ?: 'غير محدد',
                    'gps_location' => $pos->gps_location,
                    'owner_name' => $pos->owner_name,
                    'status' => 'active',
                ]
            );
        }

        if ((int) ($pos->customer_id ?? 0) !== (int) $customer->id) {
            $pos->update(['customer_id' => $customer->id]);
        }

        return $customer;
    }

    public function mapSupplyStatus(string $status): string
    {
        return match ($status) {
            'accepted', 'preparing', 'ready' => 'approved',
            'on_way' => 'out_for_delivery',
            default => $status,
        };
    }
}
