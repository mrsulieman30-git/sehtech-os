<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::where('email', '!=', 'admin@sehtech.com')->get();
foreach($users as $user) {
    echo "Deleting: " . $user->email . "\n";
    $user->delete();
}
echo "Done.\n";
