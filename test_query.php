<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Http\Request;

// Simulate what the controller does with search_number=68
$searchNumber = '68';
$parts = preg_split('/[\s,]+/', $searchNumber);
$searchNumbers = [];
foreach ($parts as $p) {
    $p = trim($p);
    if ($p !== '' && is_numeric($p)) {
        $searchNumbers[] = str_pad((int)$p, 2, '0', STR_PAD_LEFT);
    }
}
$searchNumbers = array_unique($searchNumbers);

echo "searchNumbers: " . implode(', ', $searchNumbers) . "\n";
echo "Count: " . count($searchNumbers) . "\n";
echo "empty check: " . (empty($searchNumbers) ? 'EMPTY' : 'NOT EMPTY') . "\n\n";

// Run the actual query
$query = \App\Models\DrawResult::where('prize_tier', 'GDB')
    ->whereIn('loto_number', $searchNumbers)
    ->join('draws', 'draw_results.draw_id', '=', 'draws.id')
    ->where('draws.status', 'completed')
    ->orderBy('draws.draw_date', 'desc')
    ->select('draw_results.*', 'draws.draw_date');

$matchingResults = $query->get();
echo "Total matching: " . $matchingResults->count() . "\n";

$numberResults = [];
foreach ($searchNumbers as $num) {
    $numberResults[$num] = [];
}

foreach ($matchingResults as $result) {
    $loto = $result->loto_number;
    if (isset($numberResults[$loto])) {
        $numberResults[$loto][] = [
            'date' => $result->draw_date,
            'full_number' => $result->full_number,
        ];
    }
}

foreach ($numberResults as $num => $results) {
    echo "\nNumber $num: " . count($results) . " results\n";
    foreach (array_slice($results, 0, 3) as $r) {
        echo "  " . $r['date'] . " - " . $r['full_number'] . "\n";
    }
}

// Check if the view condition works
$hasNumberSearch = !empty($searchNumbers);
$hasDateSearch = false;
$hasAny = $hasNumberSearch || $hasDateSearch;
echo "\nhasNumberSearch: " . ($hasNumberSearch ? 'true' : 'false') . "\n";
echo "hasAny: " . ($hasAny ? 'true' : 'false') . "\n";
echo "numberResults empty: " . (empty($numberResults) ? 'true' : 'false') . "\n";
