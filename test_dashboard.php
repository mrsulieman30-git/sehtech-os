<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$request = Illuminate\Http\Request::create('/api/hr/dashboard', 'GET');
$controller = new \App\Http\Controllers\Api\HrController();
try {
    $response = $controller->getDashboard($request);
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response body: " . $response->getContent() . "\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
