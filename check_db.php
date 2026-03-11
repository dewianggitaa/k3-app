<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$p3k = App\Models\P3k::where('code', 'p3k-0301001')->first();
if (!$p3k) {
    echo "P3K not found.\n";
    exit;
}
echo "P3K ID: " . $p3k->id . "\n";
if ($p3k->room) {
    echo "Room PIC ID: " . ($p3k->room->pic_user_id ?? 'NULL') . "\n";
} else {
    echo "No room.\n";
}

$inspections = App\Models\Inspection::where('assetable_type', App\Models\P3k::class)
    ->where('assetable_id', $p3k->id)
    ->where('status', 'pending')
    ->get();

foreach($inspections as $i) {
    echo 'Inspection ID: ' . $i->id . ' - User ID: ' . ($i->user_id ?? 'NULL') . "\n";
}
