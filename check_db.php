<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Active Users: " . App\Models\User::count() . "\n";
echo "Soft Deleted: " . App\Models\User::onlyTrashed()->count() . "\n";
