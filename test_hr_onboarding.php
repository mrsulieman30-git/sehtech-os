<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$request = Illuminate\Http\Request::create('/api/hr/employees', 'POST', [
    'name' => 'Test Employee',
    'email' => 'testemployee2@sehtech.com',
    'job_title' => 'Test Job',
    'employment_type' => 'full_time',
    'hire_date' => '2026-05-25',
    'salary' => '50000',
    'department_id' => '',
    'role_id' => '',
    'manager_id' => '',
]);

$controller = new \App\Http\Controllers\Api\HrController();
try {
    $response = $controller->storeEmployee($request);
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response body: " . $response->getContent() . "\n";
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "Validation failed:\n";
    print_r($e->errors());
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
