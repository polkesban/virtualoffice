<?php

return [
    'tinymce' => [
        'use_cloud' => env('TINYMCE_USE_CLOUD', false),
        'api_key' => env('TINYMCE_API_KEY'),
        'version' => env('TINYMCE_VERSION', '7'),
        'cdn_base' => env('TINYMCE_CDN_BASE', 'https://cdn.tiny.cloud/1'),
    ],
];