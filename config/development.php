<?php

return [
    // Temporary development mode to allow multiple dashboards in one browser session.
    // Keep disabled in production.
    'parallel_dashboards' => env('APP_ENV', 'production') === 'local' && env('DEV_PARALLEL_DASHBOARDS', false),
];
