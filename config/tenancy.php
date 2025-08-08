<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Super Admin Domain
    |--------------------------------------------------------------------------
    |
    | This is the domain used for super admin access
    |
    */
    'super_admin_domain' => env('TENANCY_SUPER_ADMIN_DOMAIN', 'admin.example.com'),

    /*
    |--------------------------------------------------------------------------
    | Base Domain
    |--------------------------------------------------------------------------
    |
    | This is the base domain for subdomain-based tenants
    |
    */
    'base_domain' => env('TENANCY_BASE_DOMAIN', 'example.com'),

    /*
    |--------------------------------------------------------------------------
    | Default Billing Day
    |--------------------------------------------------------------------------
    |
    | Default billing day for companies
    |
    */
    'default_billing_day' => env('TENANCY_DEFAULT_BILLING_DAY', 10),

    /*
    |--------------------------------------------------------------------------
    | Default VAT Percent
    |--------------------------------------------------------------------------
    |
    | Default VAT percentage for companies
    |
    */
    'default_vat_percent' => env('TENANCY_DEFAULT_VAT_PERCENT', 0.00),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | Default currency for companies
    |
    */
    'default_currency' => env('TENANCY_DEFAULT_CURRENCY', 'BDT'),

    /*
    |--------------------------------------------------------------------------
    | Default Timezone
    |--------------------------------------------------------------------------
    |
    | Default timezone for companies
    |
    */
    'default_timezone' => env('TENANCY_DEFAULT_TIMEZONE', 'Asia/Dhaka'),
];