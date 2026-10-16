<?php

return [
    'disk' => env('MEDIA_STORAGE_DISK', 'public'),

    'local_disk' => env('MEDIA_STORAGE_LOCAL_DISK', 'public'),

    'google_drive' => [
        'application_name' => env('GOOGLE_DRIVE_APPLICATION_NAME', env('APP_NAME', 'Poltekkes Bandung Media Storage')),
        'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
        'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID', 'root'),
        'share_publicly' => env('GOOGLE_DRIVE_SHARE_PUBLICLY', true),
    ],
];