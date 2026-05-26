<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::withTrashed()->where('email', 'mohammad@sehtech.com')->first();
if ($user) {
    $user->restore();
    echo "Restored Mohammad!\n";
} else {
    echo "Could not find Mohammad.\n";
}
