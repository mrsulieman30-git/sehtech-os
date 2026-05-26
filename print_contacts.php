<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$contacts = App\Models\Contact::withTrashed()->get();
echo "ID | Name | Email | Deleted At\n";
echo str_repeat("-", 80) . "\n";
foreach ($contacts as $c) {
    echo "{$c->id} | {$c->name} | {$c->email} | {$c->deleted_at}\n";
}
