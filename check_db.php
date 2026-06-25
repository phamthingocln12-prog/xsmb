<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$draw = App\Models\Draw::where('draw_date', '2026-03-29')->first();
if ($draw) {
    echo "Draw ID: " . $draw->id . PHP_EOL;
    echo "Status: " . $draw->status . PHP_EOL;
    echo "Results count: " . $draw->results()->count() . PHP_EOL;
} else {
    echo "NO DRAW for 2026-03-29" . PHP_EOL;
}
