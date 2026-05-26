<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\Storage;
Storage::disk('local')->put('projects/test_trash.txt', 'hello');
$r = Storage::disk('local')->move('projects/test_trash.txt', 'projects/.trash/test_trash.txt');
echo 'Result: ' . ($r ? 'true' : 'false') . PHP_EOL;
echo 'Exists original: ' . (Storage::disk('local')->exists('projects/test_trash.txt') ? 'true' : 'false') . PHP_EOL;
