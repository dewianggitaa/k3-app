<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\Apar;
use App\Models\Hydrant;
use App\Models\P3k;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'p3k');
        
        $now = Carbon::now();
        $startMonth = $now->month % 2 === 0 ? $now->month - 1 : $now->month;
        
        $startDate = $request->query('start_date', $now->copy()->month($startMonth)->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', $now->copy()->month($startMonth + 1)->endOfMonth()->toDateString());
        
        $activityType = $request->query('activity_type', 'all'); 
        $assetCode = $request->query('asset_code', 'all'); 

        $checklistParams = DB::table('checklist_parameters')->get()->keyBy('id')->toArray();
        $itemNames = DB::table('p3k_items')->pluck('name', 'id')->toArray();

        $assetsList = [
            'p3k' => P3k::select('code')->orderBy('code')->get(),
            'apar' => Apar::select('code')->orderBy('code')->get(),
            'hydrant' => Hydrant::select('code')->orderBy('code')->get(),
        ];

        $data = collect();

        if ($tab === 'p3k') {
            if (in_array($activityType, ['all', 'usage'])) {
                $usages = DB::table('p3k_usages')->join('p3ks', 'p3k_usages.p3k_id', '=', 'p3ks.id')->join('p3k_items', 'p3k_usages.p3k_item_id', '=', 'p3k_items.id')->leftJoin('departments', 'p3k_usages.department_id', '=', 'departments.id')
                    ->whereBetween('p3k_usages.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->when($assetCode !== 'all', fn($q) => $q->where('p3ks.code', $assetCode))
                    ->select('p3k_usages.id', 'p3k_usages.created_at as record_date', 'p3ks.code as asset_code', 'p3k_usages.type', 'p3k_usages.reporter_name', 'departments.name as department_name', 'p3k_items.name as item_name', 'p3k_usages.qty', 'p3k_usages.notes')
                    ->get()->map(function ($item) {
                        return [
                            'id' => 'usage_' . $item->id, 'record_date' => $item->record_date, 'asset_code' => $item->asset_code,
                            'action_type' => $item->type === 'in' ? 'PENAMBAHAN' : 'PEMAKAIAN',
                            'actor' => $item->reporter_name . ($item->department_name ? " ({$item->department_name})" : ''),
                            'details' => "Item P3K: {$item->item_name}\nJumlah: {$item->qty} " . ($item->notes ? " (Catatan: {$item->notes})" : ''),
                        ];
                    });
                $data = $data->concat($usages);
            }

            if (in_array($activityType, ['all', 'inspection'])) {
                $inspections = Inspection::with(['user', 'assetable'])
                    ->where('assetable_type', P3k::class)
                    // PERBAIKAN: Ambil semua yang sudah dikerjakan, bukan cuma 'completed'
                    ->whereNotIn('status', ['pending', 'overdue'])
                    ->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->when($assetCode !== 'all', fn($q) => $q->whereHasMorph('assetable', [P3k::class], fn($q2) => $q2->where('code', $assetCode)))
                    ->get()->map(function ($inspection) use ($checklistParams, $itemNames) {
                        return [
                            'id' => 'insp_' . $inspection->id, 'record_date' => $inspection->updated_at, 'asset_code' => $inspection->assetable->code ?? '-',
                            'action_type' => 'INSPEKSI RUTIN', 'actor' => $inspection->user->name ?? 'Sistem K3',
                            'details' => $this->buildDetail($inspection, $checklistParams, $itemNames),
                        ];
                    });
                $data = $data->concat($inspections);
            }
            $data = $data->sortByDesc('record_date')->values();
            
            $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
            $perPage = 15;
            $data = new LengthAwarePaginator($data->forPage($page, $perPage)->values(), $data->count(), $perPage, $page, ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]);
            $data->appends($request->all()); 
                
        } elseif ($tab === 'apar' || $tab === 'hydrant') {
            $modelClass = $tab === 'apar' ? Apar::class : Hydrant::class;
            
            $allInspections = Inspection::with(['user.department', 'assetable'])
                ->where('assetable_type', $modelClass)
                ->whereNotIn('status', ['pending', 'overdue'])
                ->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->when($assetCode !== 'all', fn($q) => $q->whereHasMorph('assetable', [$modelClass], fn($q2) => $q2->where('code', $assetCode)))
                ->get();

            $groupedData = $this->groupAndFormatK3Reports($allInspections, $checklistParams, $itemNames);

            $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
            $perPage = 10;
            $data = new LengthAwarePaginator($groupedData->forPage($page, $perPage)->values(), $groupedData->count(), $perPage, $page, ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]);
            $data->appends($request->all());
        }

        return Inertia::render('Reports/Index', [
            'activeTab' => $tab, 'assetsList' => $assetsList, 
            'filters' => ['start_date' => $startDate, 'end_date' => $endDate, 'activity_type' => $activityType, 'asset_code' => $assetCode],
            'reports' => $data
        ]);
    }

    public function exportPdf(Request $request)
    {
        $tab = $request->query('tab', 'p3k');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $assetCode = $request->query('asset_code', 'all'); 

        $checklistParams = DB::table('checklist_parameters')->get()->keyBy('id')->toArray();
        $itemNames = DB::table('p3k_items')->pluck('name', 'id')->toArray();

        $pdfView = 'pdf.' . $tab;
        $data = collect();

        $supervisor = User::where('is_active', true)
            ->whereHas('position', function($q) {
                $q->where('name', 'Supervisor');
            })
            ->whereHas('department', function($q) {
                $q->where('name', 'K3');
            })
            ->first();

        $supervisorName = $supervisor ? $supervisor->name : '';

        if ($tab === 'p3k') {
            $queryInsp = Inspection::with(['user', 'assetable'])
                ->where('assetable_type', P3k::class)
                ->whereNotIn('status', ['pending', 'overdue'])
                ->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            
            if ($assetCode !== 'all') {
                $queryInsp->whereHasMorph('assetable', [P3k::class], fn($q) => $q->where('code', $assetCode));
            }
            
            $data = $queryInsp->get()->map(function ($inspection) use ($checklistParams, $itemNames) {
                return [
                    'record_date' => $inspection->updated_at, 'asset_code' => $inspection->assetable->code ?? '-',
                    'action_type' => 'INSPEKSI RUTIN', 'actor' => $inspection->user->name ?? 'Sistem K3', 
                    'details' => $this->buildDetail($inspection, $checklistParams, $itemNames),
                ];
            });
        } else {
            $modelClass = $tab === 'apar' ? Apar::class : Hydrant::class;
            
            $allInspections = Inspection::with(['user.department', 'assetable'])
                ->where('assetable_type', $modelClass)
                // PERBAIKAN: Ambil semua yang sudah dikerjakan
                ->whereNotIn('status', ['pending', 'overdue'])
                ->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->when($assetCode !== 'all', fn($q) => $q->whereHasMorph('assetable', [$modelClass], fn($q2) => $q2->where('code', $assetCode)))
                ->get();

            $data = $this->groupAndFormatK3Reports($allInspections, $checklistParams, $itemNames);
        }

        $pdf = Pdf::loadView($pdfView, [
            'data' => $data, 'tab' => strtoupper($tab), 'startDate' => $startDate, 'endDate' => $endDate, 'selectedAsset' => $assetCode, 
            'printedBy' => auth()->user()->name ?? 'Sistem K3',
            'printedByDepartment' => auth()->user()->department->name ?? 'K3',
            'supervisor' => $supervisorName,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('Laporan_K3_' . strtoupper($tab) . '.pdf');
    }

    private function groupAndFormatK3Reports($inspections, $checklistParams, $itemNames)
    {
        $groupedByAsset = $inspections->groupBy('assetable_id');

        return $groupedByAsset->map(function($assetInspections) use ($checklistParams, $itemNames) {
            $asset = $assetInspections->first()->assetable;
            
            $picReports = $assetInspections->filter(fn($i) => optional($i->user->department)->name !== 'K3')
                ->sortBy('updated_at')->take(2)
                ->map(function($pic) use ($checklistParams, $itemNames) {
                    $detailString = $this->buildDetail($pic, $checklistParams, $itemNames);

                    $reportData = is_string($pic->report_data) ? json_decode($pic->report_data, true) : $pic->report_data;

                    return [
                        'id' => $pic->id,
                        'actor' => $pic->user->name,
                        'record_date' => $pic->updated_at,
                        'status' => str_contains($detailString, 'KRITIS') ? 'KRITIS' : 'SAFE', 
                        'details' => $detailString,
                        'notes' => $reportData['notes'] ?? null
                    ];
                })->values();

            $k3Report = $assetInspections->filter(fn($i) => optional($i->user->department)->name === 'K3')
                ->sortByDesc('updated_at')->first();

            $reportDataK3 = $k3Report ? (is_string($k3Report->report_data) ? json_decode($k3Report->report_data, true) : $k3Report->report_data) : [];

            $statusPenggantian = 'Tidak Diganti';
            if (isset($reportDataK3['answers']) && is_array($reportDataK3['answers'])) {
                foreach ($reportDataK3['answers'] as $paramId => $ans) {
                    $val = is_array($ans) ? ($ans['response'] ?? null) : $ans;
                    
                    if (isset($checklistParams[$paramId]) && $checklistParams[$paramId]->input_type === 'date' && !empty($val)) {
                        $statusPenggantian = 'Diganti<br><span style="font-size: 8px; color: #6b7280;">(Exp: ' . \Carbon\Carbon::parse($val)->format('d M Y') . ')</span>';
                        break;
                    }
                }
            }

            if ($picReports->isEmpty()) {
                 $picReports->push([
                     'id' => 'dummy_' . uniqid(), 'actor' => '-', 'record_date' => null, 'status' => '-', 'details' => ''
                 ]);
            }

            return [
                'id' => $k3Report->id ?? 'asset_' . $asset->id,
                'asset_code' => $asset->code ?? '-',
                'actor_k3' => $k3Report->user->name ?? 'Belum Divalidasi K3',
                'record_date_k3' => $k3Report->updated_at ?? null,
                'tindakan' => $reportDataK3['notes'] ?? $reportDataK3['admin_notes'] ?? $k3Report->notes ?? '-',
                'kondisi_akhir' => strtoupper($asset->status ?? 'SAFE'),
                'pic_reports' => $picReports->toArray(),
                'status_penggantian' => $statusPenggantian,
            ];
        })->values()->sortByDesc('record_date_k3');
    }

    private function buildDetail($inspection, $checklistParams, $itemNames)
    {
        $report = is_string($inspection->report_data) ? json_decode($inspection->report_data, true) : $inspection->report_data;
        $anomalies = [];

        if (isset($report['answers']) && is_array($report['answers'])) {
            foreach ($report['answers'] as $paramId => $ans) {
                // Ambil jawaban persis seperti di controller update
                $userAnswer = is_array($ans) ? ($ans['response'] ?? null) : $ans;
                
                if (isset($checklistParams[$paramId])) {
                    $param = $checklistParams[$paramId];
                    
                    // Kita bypass input angka/number karena sudah dicek di bagian quantities bawah
                    if (($param->input_type ?? '') === 'number') {
                        continue;
                    }

                    $standardVal = $param->standard_value ?? '';

                    // JIKA STANDARNYA KOSONG DI DATABASE, KITA ABAIKAN (Supaya tidak false alarm)
                    if (trim($standardVal) === '') {
                        continue;
                    }

                    // LOGIKA SAMA PERSIS DENGAN FUNGSI UPDATE MILIKMU:
                    // Jika ada jawaban, DAN jawabannya tidak sama dengan nilai standar -> Masuk Rincian Rusak
                    if ($userAnswer && trim($userAnswer) != trim($standardVal)) {
                        $label = $param->label ?? $param->name ?? 'Komponen';
                        $anomalies[] = $label . ': ' . $userAnswer;
                    }
                }
            }
        }
        
        // P3K Logic for Item Quantities
        if (isset($report['quantities']) && is_array($report['quantities'])) {
            foreach ($report['quantities'] as $itemId => $qtyData) {
                $actual = (int) ($qtyData['actual'] ?? 0);
                $expected = (int) ($qtyData['expected'] ?? 0);
                if ($actual < $expected) {
                    $label = $itemNames[$itemId] ?? 'Item ' . $itemId;
                    $anomalies[] = $label . ': Kurang ' . ($expected - $actual);
                }
            }
        }
        
        if (!empty($anomalies)) return "Kondisi: KRITIS\nRincian: " . implode(', ', $anomalies);
        return "Kondisi: BAIK";
    }
}