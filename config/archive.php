<?php

return [
    // Number of days to keep rows in live tables before moving them to archive tables.
    'retention_days' => [
        'audit_logs' => (int) env('ARCHIVE_AUDIT_LOGS_RETENTION_DAYS', 90),
        'inventory_movements' => (int) env('ARCHIVE_INVENTORY_MOVEMENTS_RETENTION_DAYS', 180),
        'order_status_histories' => (int) env('ARCHIVE_ORDER_STATUS_HISTORIES_RETENTION_DAYS', 120),
        'distributor_location_logs' => (int) env('ARCHIVE_DISTRIBUTOR_LOCATION_LOGS_RETENTION_DAYS', 60),
    ],

    // Number of days to keep rows in archive tables before hard cleanup.
    // Set to 0 or negative to disable archive cleanup for that table.
    'archive_cleanup_days' => [
        'audit_logs' => (int) env('ARCHIVE_AUDIT_LOGS_CLEANUP_DAYS', 1460),
        'inventory_movements' => (int) env('ARCHIVE_INVENTORY_MOVEMENTS_CLEANUP_DAYS', 1825),
        'order_status_histories' => (int) env('ARCHIVE_ORDER_STATUS_HISTORIES_CLEANUP_DAYS', 1825),
        'distributor_location_logs' => (int) env('ARCHIVE_DISTRIBUTOR_LOCATION_LOGS_CLEANUP_DAYS', 730),
    ],

    'batch_size' => (int) env('ARCHIVE_BATCH_SIZE', 1000),

    // To protect production from large deletes in one run.
    'max_batches_per_table' => (int) env('ARCHIVE_MAX_BATCHES_PER_TABLE', 20),
];
