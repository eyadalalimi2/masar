<?php

namespace App\Services\Customer;

use App\Models\Finance\Account;
use App\Models\Consumer;

class ConsumerService
{
    public function create(array $data): Consumer
    {
        return Consumer::create($data);
    }

    public function update(Consumer $consumer, array $data): Consumer
    {
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $consumer->update($data);

        return $consumer->fresh();
    }

    public function toggleStatus(Consumer $consumer): Consumer
    {
        $consumer->update([
            'status' => $consumer->status === Account::STATUS_ACTIVE ? Account::STATUS_INACTIVE : Account::STATUS_ACTIVE,
        ]);

        return $consumer->fresh();
    }

    public function delete(Consumer $consumer): void
    {
        $consumer->delete();
    }
}
