<?php

return [
    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase/service-account.json')),
    ],
    'database' => [
        'url' => env('FIREBASE_DATABASE_URL'),
    ],
    'project_id' => env('FIREBASE_PROJECT_ID'),
];
