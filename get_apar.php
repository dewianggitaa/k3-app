<?php
$apar = App\Models\Apar::where('code', 'APAR-0301007')->first();
$inspections = App\Models\Inspection::where('assetable_id', $apar->id)->where('assetable_type', 'App\Models\Apar')->orderBy('schedule_date')->get();
echo $inspections->toJson(JSON_PRETTY_PRINT);
