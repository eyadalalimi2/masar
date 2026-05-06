<?php

namespace App\Support;

final class OptionLists
{
    public const ACTIVE_INACTIVE = ['active', 'inactive'];

    public const CUSTOMER_TYPES = ['workshop', 'retail_store'];

    public const BROADCAST_TARGET_TYPES = ['all', 'suppliers', 'branches', 'distributors', 'customers', 'consumers'];

    public const WORKSHOP_SERVICE_ORDER_STATUSES = ['requested', 'in_progress', 'completed', 'cancelled'];

    public const WORKSHOP_PURCHASE_ORDER_STATUSES = ['pending', 'approved', 'in_transit', 'received', 'cancelled'];

    public const POS_SALE_CHANNELS = ['online', 'offline'];
}
