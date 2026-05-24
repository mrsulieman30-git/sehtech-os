<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SystemSetting;
$settings = SystemSetting::all();
foreach($settings as $s) {
    echo "Key: " . $s->key . " -> " . json_encode($s->value) . "\n";
}
