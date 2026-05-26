<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$userCount = App\Models\User::count();
echo "Total users before: $userCount\n";

$user = App\Models\User::where('email', '!=', 'admin@sehtech.com')->first();
if ($user) {
    echo "Deleting user: " . $user->email . "\n";
    $controller = new App\Http\Controllers\Api\AdminController();
    $controller->destroy($user->id);
    
    $userCountAfter = App\Models\User::count();
    echo "Total users after: $userCountAfter\n";
    
    $users = App\Models\User::all();
    echo "Is deleted user in the list? " . ($users->contains('id', $user->id) ? "Yes" : "No") . "\n";
} else {
    echo "No users to delete.\n";
}
