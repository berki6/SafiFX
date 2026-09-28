<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SafiFX Administrator Bootstrap Configuration
    |--------------------------------------------------------------------------
    |
    | Used by DatabaseSeeder to seed the initial system administrator.
    | Production seeding requires ADMIN_PASSWORD to be explicitly set in .env.
    |
    */
    'admin' => [
        'name' => env('ADMIN_NAME', 'SafiFX Admin'),
        'email' => env('ADMIN_EMAIL', 'admin@safifx.com'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Public & Support Contact Information
    |--------------------------------------------------------------------------
    */
    'contact' => [
        'email' => env('CONTACT_EMAIL', 'support@safifx.com'),
        'phone' => env('CONTACT_PHONE', '+254 700 000 000'),
        'location' => env('CONTACT_LOCATION', 'Nairobi, Kenya'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Trading & Platform Defaults
    |--------------------------------------------------------------------------
    |
    | Supported corridors are no longer a config array — they come from the
    | `countries` and `exchange_rates` tables (see CountrySeeder), which the
    | administrator manages from the Filament admin panel per docs/SAFIFX.md §2/§6.
    | `base_currency` is SafiFX's internal settlement/reporting currency only.
    |
    */
    'platform' => [
        'name' => env('SAFIFX_PLATFORM_NAME', 'SafiFX Trading Platform'),
        'base_currency' => env('SAFIFX_BASE_CURRENCY', 'USD'),
    ],

];
