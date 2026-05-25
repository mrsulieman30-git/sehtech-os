<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Authenticate user 1 for the test
$user = \App\Models\User::first();
if ($user) {
    \Illuminate\Support\Facades\Auth::login($user);
}

$response = $kernel->handle(
    $request = Illuminate\Http\Request::create(
        '/api/agents/master-chat',
        'POST',
        ['message' => 'Add an employee named John Doe with email john@sehtech.com to the HR department as an HR Assistant with a salary of 50000.']
    )
);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Response: " . $response->getContent() . "\n";
