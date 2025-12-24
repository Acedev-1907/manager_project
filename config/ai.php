<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Groq AI Configuration
    |--------------------------------------------------------------------------
    |
    | Groq API configuration for AI chat functionality
    | Get API key: https://console.groq.com/keys
    |
    */

    'groq_api_key' => env('GROQ_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Groq Model
    |--------------------------------------------------------------------------
    |
    | Default model to use: llama-3.1-8b-instant (fast and free)
    |
    */

    'groq_model' => env('GROQ_MODEL', 'llama-3.1-8b-instant'),
];
