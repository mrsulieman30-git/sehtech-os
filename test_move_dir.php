<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\Storage;
Storage::disk('local')->makeDirectory('projects/test_dir');
Storage::disk('local')->put('projects/test_dir/file.txt', 'hello');
$r = Storage::disk('local')->move('projects/test_dir', 'projects/.trash/test_dir');
echo 'Result: ' . ($r ? 'true' : 'false') . PHP_EOL;
echo 'Exists original: ' . (Storage::disk('local')->directoryExists('projects/test_dir') ? 'true' : 'false') . PHP_EOL;
