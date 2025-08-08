<?php
// Script to test directory structure

echo "Testing directory structure...\n\n";

$directories = [
    'app',
    'app/Console',
    'app/Console/Commands',
    'app/Exceptions',
    'app/Http',
    'app/Http/Controllers',
    'app/Http/Controllers/Api',
    'app/Http/Controllers/Api/V1',
    'app/Http/Controllers/Api/V1/Admin',
    'app/Http/Controllers/Api/V1/Customer',
    'app/Http/Controllers/Api/V1/Reseller',
    'app/Http/Controllers/Api/V1/SuperAdmin',
    'app/Http/Controllers/Web',
    'app/Http/Controllers/Web/Admin',
    'app/Http/Controllers/Web/Customer',
    'app/Http/Controllers/Web/Reseller',
    'app/Http/Controllers/Web/SuperAdmin',
    'app/Http/Controllers/SuperAdmin',
    'app/Http/Middleware',
    'app/Http/Requests',
    'app/Http/Resources',
    'app/Models',
    'app/Providers',
    'app/Services',
    'app/Repositories',
    'app/Jobs',
    'app/Events',
    'app/Listeners',
    'app/Notifications',
    'app/Rules',
    'app/Traits',
    'app/Helpers',
    'bootstrap',
    'bootstrap/cache',
    'config',
    'database',
    'database/factories',
    'database/migrations',
    'database/seeders',
    'public',
    'public/css',
    'public/js',
    'public/images',
    'resources',
    'resources/js',
    'resources/css',
    'resources/views',
    'routes',
    'storage',
    'storage/app',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs'
];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        echo "✓ $dir\n";
    } else {
        echo "✗ $dir (MISSING)\n";
    }
}

echo "\nDirectory structure test completed.\n";