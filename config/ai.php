<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Provider
    |--------------------------------------------------------------------------
    | 'openai'  — GPT-4o-mini + text-embedding-3-small (recommended)
    | 'openrouter' — OpenRouter (OpenAI-compatible routing)
    | 'gemini'  — Gemini 1.5 Flash + text-embedding-004
    */
    'provider' => env('AI_PROVIDER', 'openai'),

    'openai' => [
        'api_key'      => env('OPENAI_API_KEY', ''),
        'org_id'       => env('OPENAI_ORG_ID', ''),
        'chat_model'   => env('OPENAI_CHAT_MODEL',   'gpt-4o-mini'),
        'vision_model' => env('OPENAI_VISION_MODEL', 'gpt-4o-mini'),
        'transcribe_model' => env('OPENAI_TRANSCRIBE_MODEL', 'whisper-1'),
        'embed_model'  => env('OPENAI_EMBED_MODEL',  'text-embedding-3-small'),
        'embed_dims'   => (int) env('OPENAI_EMBED_DIMS', 1536),
        'timeout'      => (int) env('AI_TIMEOUT', 45),
        'transcribe_timeout' => (int) env('AI_TRANSCRIBE_TIMEOUT', 120),
    ],

    'openrouter' => [
        'api_key'      => env('OPENROUTER_API_KEY', ''),
        'base_url'     => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'site_url'     => env('OPENROUTER_SITE_URL', env('APP_URL', 'http://localhost')),
        'app_name'     => env('OPENROUTER_APP_NAME', env('APP_NAME', 'Laravel')),
        'chat_model'   => env('OPENROUTER_CHAT_MODEL', 'openai/gpt-4o-mini'),
        'vision_model' => env('OPENROUTER_VISION_MODEL', env('OPENROUTER_CHAT_MODEL', 'openai/gpt-4o-mini')),
        'embed_model'  => env('OPENROUTER_EMBED_MODEL', 'openai/text-embedding-3-small'),
        'embed_dims'   => (int) env('OPENROUTER_EMBED_DIMS', 1536),
        'timeout'      => (int) env('AI_TIMEOUT', 45),
    ],

    'gemini' => [
        'api_key'      => env('GEMINI_API_KEY', ''),
        'chat_model'   => env('GEMINI_CHAT_MODEL',   'gemini-1.5-flash'),
        'vision_model' => env('GEMINI_VISION_MODEL', 'gemini-1.5-flash'),
        'transcribe_model' => env('GEMINI_TRANSCRIBE_MODEL', env('GEMINI_CHAT_MODEL', 'gemini-1.5-flash')),
        'embed_model'  => env('GEMINI_EMBED_MODEL',  'text-embedding-004'),
        'embed_dims'   => (int) env('GEMINI_EMBED_DIMS', 768),
        'timeout'      => (int) env('AI_TIMEOUT', 45),
        'transcribe_timeout' => (int) env('AI_TRANSCRIBE_TIMEOUT', 120),
        'base_url'     => 'https://generativelanguage.googleapis.com/v1beta',
    ],

    /*
    |--------------------------------------------------------------------------
    | RAG (Retrieval-Augmented Generation) Settings
    |--------------------------------------------------------------------------
    */
    'rag' => [
        'chunk_size'    => (int)   env('RAG_CHUNK_SIZE',    500),   // chars per chunk
        'chunk_overlap' => (int)   env('RAG_CHUNK_OVERLAP', 80),    // overlap chars
        'top_k'         => (int)   env('RAG_TOP_K',          5),    // chunks to retrieve
        'min_score'     => (float) env('RAG_MIN_SCORE',      0.30), // cosine threshold
    ],

    /*
    |--------------------------------------------------------------------------
    | Virtual Concierge Settings
    |--------------------------------------------------------------------------
    */
    'concierge' => [
        'default_language' => env('CONCIERGE_DEFAULT_LANGUAGE', 'id'),
        'supported_languages' => [
            'id' => 'Bahasa Indonesia',
            'en' => 'English',
        ],
        'max_history'  => (int)   env('CONCIERGE_MAX_HISTORY', 6),    // conversation turns kept
        'temperature'  => (float) env('CONCIERGE_TEMPERATURE', 0.4),
        'max_tokens'   => (int)   env('CONCIERGE_MAX_TOKENS',  1024),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Content Engine Settings
    |--------------------------------------------------------------------------
    */
    'content_engine' => [
        'temperature'  => (float) env('CE_TEMPERATURE',   0.80),
        'max_tokens'   => (int)   env('CE_MAX_TOKENS',    2000),
    ],

    'website_translation' => [
        'enabled' => (bool) env('WEBSITE_TRANSLATION_ENABLED', true),
        'locale_cookie' => env('WEBSITE_TRANSLATION_COOKIE', 'polkesban_locale'),
        'batch_size' => (int) env('WEBSITE_TRANSLATION_BATCH_SIZE', 20),
        'max_chars_per_batch' => (int) env('WEBSITE_TRANSLATION_MAX_CHARS', 2800),
        'max_text_length' => (int) env('WEBSITE_TRANSLATION_MAX_TEXT_LENGTH', 800),
        'cache_hours' => (int) env('WEBSITE_TRANSLATION_CACHE_HOURS', 720),
    ],
];
