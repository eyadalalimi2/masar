<?php

return [
    'contract' => [
        'bi' => [
            'sales_7d',
            'sales_growth_percent_7d',
            'delivered_orders_7d',
            'orders_growth_percent_7d',
            'pending_orders_total',
            'sla_on_time_percent_30d',
            'customer_growth_30d',
            'customer_growth_delta_30d',
        ],
        'monitoring' => [
            'failed_jobs_count',
            'alerts_last_15m',
            'active_delivery_now',
            'write_pressure_indicator',
            'sla_on_time_percent_30d',
            'monitoring_overall_state',
        ],
    ],
];
