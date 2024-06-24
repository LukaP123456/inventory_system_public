<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // 'allowed_origins' => [
    //     '*',
    //     'http://localhost:3000',
    //     'http://192.168.1.106:19000',
    //     'https://dark-desert-588371.postman.co/workspace/My-Workspace~1c8ec03f-a7dd-436f-844a-a7a91714e3cf/request/23637998-d0acabf2-4543-42b9-b40b-75914d022384'
    //     ,'http://127.0.0.1:8001/'    ],

    'allowed_origins' => ['*'],



    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
