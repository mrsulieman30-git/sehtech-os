<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/api/admin/users', 'GET');
$controller = new App\Http\Controllers\Api\AdminController();
$response = $controller->index($request);
$data = json_decode($response->getContent(), true);

echo "Total users returned by API: " . count($data['users']) . "\n";
foreach($data['users'] as $u) {
    echo $u['email'] . "\n";
}
