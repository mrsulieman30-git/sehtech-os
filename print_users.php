<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::withTrashed()->get();
echo "ID | Name | Email | Status | Deleted At\n";
echo str_repeat("-", 80) . "\n";
foreach ($users as $u) {
    echo "{$u->id} | {$u->name} | {$u->email} | {$u->status} | {$u->deleted_at}\n";
}
