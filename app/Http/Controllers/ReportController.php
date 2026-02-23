<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\Apar;
use App\Models\Hydrant;
use App\Models\P3k;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'p3k');
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());
        $activityType = $request->query('activity_type', 'all'); 
        $assetCode = $request->query('asset_code', 'all'); 

        // 1. KAMUS DATA & STANDAR
        // Ambil data checklist_parameters (termasuk standard_value)
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
                            'action_type' => $item->type === 'in' ? 'STOK MASUK' : 'PEMAKAIAN',
                            'actor' => $item->reporter_name . ($item->department_name ? " ({$item->department_name})" : ''),
                            'details' => "Mutasi Obat: {$item->item_name} (Qty: {$item->qty})" . ($item->notes ? " - {$item->notes}" : ''),
                        ];
                    });
                $data = $data->concat($usages);
            }

            if (in_array($activityType, ['all', 'inspection'])) {
                $inspections = Inspection::with(['user', 'assetable'])->where('assetable_type', P3k::class)->where('status', 'completed')
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
            $query = Inspection::with(['user', 'assetable'])->where('assetable_type', $modelClass)->where('status', 'completed')->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            
            if ($assetCode !== 'all') {
                $query->whereHasMorph('assetable', [$modelClass], fn($q) => $q->where('code', $assetCode));
            }
            $paginator = $query->orderBy('updated_at', 'desc')->paginate(15)->withQueryString();

            $paginator->getCollection()->transform(function ($inspection) use ($checklistParams, $itemNames) {
                return [
                    'id' => 'insp_' . $inspection->id, 'record_date' => $inspection->updated_at, 'asset_code' => $inspection->assetable->code ?? '-',
                    'action_type' => 'INSPEKSI RUTIN', 'actor' => $inspection->user->name ?? 'Sistem K3',
                    'details' => $this->buildDetail($inspection, $checklistParams, $itemNames),
                ];
            });
            $data = $paginator;
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
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());
        $activityType = $request->query('activity_type', 'all'); 
        $assetCode = $request->query('asset_code', 'all'); 

        // KAMUS DATA & STANDAR UNTUK PDF
        $checklistParams = DB::table('checklist_parameters')->get()->keyBy('id')->toArray();
        $itemNames = DB::table('p3k_items')->pluck('name', 'id')->toArray();

        $data = collect();

        if ($tab === 'p3k') {
            if (in_array($activityType, ['all', 'usage'])) {
                $usages = DB::table('p3k_usages')->join('p3ks', 'p3k_usages.p3k_id', '=', 'p3ks.id')->join('p3k_items', 'p3k_usages.p3k_item_id', '=', 'p3k_items.id')->leftJoin('departments', 'p3k_usages.department_id', '=', 'departments.id')
                    ->whereBetween('p3k_usages.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->when($assetCode !== 'all', fn($q) => $q->where('p3ks.code', $assetCode))
                    ->select('p3k_usages.created_at as record_date', 'p3ks.code as asset_code', 'p3k_usages.type', 'p3k_usages.reporter_name', 'departments.name as department_name', 'p3k_items.name as item_name', 'p3k_usages.qty', 'p3k_usages.notes')
                    ->get()->map(function ($item) {
                        return [
                            'record_date' => $item->record_date, 'asset_code' => $item->asset_code, 'action_type' => $item->type === 'in' ? 'STOK MASUK' : 'PEMAKAIAN',
                            'actor' => $item->reporter_name . ($item->department_name ? " ({$item->department_name})" : ''), 
                            'details' => "Mutasi Obat: {$item->item_name} (Qty: {$item->qty})" . ($item->notes ? " - {$item->notes}" : ''),
                        ];
                    });
                $data = $data->concat($usages);
            }

            if (in_array($activityType, ['all', 'inspection'])) {
                $inspections = Inspection::with(['user', 'assetable'])->where('assetable_type', P3k::class)->where('status', 'completed')
                    ->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->when($assetCode !== 'all', fn($q) => $q->whereHasMorph('assetable', [P3k::class], fn($q2) => $q2->where('code', $assetCode)))
                    ->get()->map(function ($inspection) use ($checklistParams, $itemNames) {
                        return [
                            'record_date' => $inspection->updated_at, 'asset_code' => $inspection->assetable->code ?? '-',
                            'action_type' => 'INSPEKSI RUTIN', 'actor' => $inspection->user->name ?? 'Sistem K3', 
                            'details' => $this->buildDetail($inspection, $checklistParams, $itemNames),
                        ];
                    });
                $data = $data->concat($inspections);
            }
            $data = $data->sortByDesc('record_date')->values();
                
        } elseif ($tab === 'apar' || $tab === 'hydrant') {
            $modelClass = $tab === 'apar' ? Apar::class : Hydrant::class;
            $query = Inspection::with(['user', 'assetable'])->where('assetable_type', $modelClass)->where('status', 'completed')->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            
            if ($assetCode !== 'all') {
                $query->whereHasMorph('assetable', [$modelClass], fn($q) => $q->where('code', $assetCode));
            }
            $rawData = $query->orderBy('updated_at', 'desc')->get();

            $data = $rawData->map(function ($inspection) use ($checklistParams, $itemNames) {
                return [
                    'record_date' => $inspection->updated_at, 'asset_code' => $inspection->assetable->code ?? '-',
                    'action_type' => 'INSPEKSI RUTIN', 'actor' => $inspection->user->name ?? 'Sistem K3', 
                    'details' => $this->buildDetail($inspection, $checklistParams, $itemNames),
                ];
            });
        }

        $pdf = Pdf::loadView('reports.pdf', [
            'data' => $data, 'tab' => strtoupper($tab), 'startDate' => $startDate, 'endDate' => $endDate, 'selectedAsset' => $assetCode, 
            'printedBy' => auth()->user()->name ?? 'Sistem K3',
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('Laporan_K3_' . strtoupper($tab) . '.pdf');
    }

    /**
     * PRIVATE FUNCTION: Logic Baru (Bandingkan dengan Standar)
     */
    private function buildDetail($inspection, $checklistParams, $itemNames)
    {
        $report = is_string($inspection->report_data) ? json_decode($inspection->report_data, true) : $inspection->report_data;
        $anomalies = [];

        // 1. Cek Parameter Form (APAR & Hydrant)
        if (isset($report['answers']) && is_array($report['answers'])) {
            foreach ($report['answers'] as $paramId => $ans) {
                $responseStr = is_array($ans) ? ($ans['response'] ?? '') : $ans;
                
                // Ambil data standar dari tabel checklist_parameters
                if (isset($checklistParams[$paramId])) {
                    $param = $checklistParams[$paramId];
                    // Pakai label atau name tergantung struktur tabelmu
                    $label = $param->label ?? $param->name ?? 'Parameter ' . $paramId;
                    $standard = $param->standard_value;

                    // BANDINGKAN JAWABAN vs STANDAR (mengabaikan huruf besar/kecil)
                    if (strtolower(trim($responseStr)) !== strtolower(trim($standard))) {
                        $anomalies[] = $label . ': ' . $responseStr;
                    }
                }
            }
        }

        // 2. Cek Kuantitas (P3K)
        // Di JSON `report_data`, key `expected` itu adalah nilai standar dari p3k_type_items
        if (isset($report['quantities']) && is_array($report['quantities'])) {
            foreach ($report['quantities'] as $itemId => $qtyData) {
                $actual = (int) ($qtyData['actual'] ?? 0);
                $expected = (int) ($qtyData['expected'] ?? 0); // Ini adalah nilai dari kolom standard
                
                // BANDINGKAN JUMLAH AKTUAL vs STANDAR
                if ($actual < $expected) {
                    $label = $itemNames[$itemId] ?? 'Item ' . $itemId;
                    $kurang = $actual - $expected; // Hasil otomatis minus (contoh: 8 - 10 = -2)
                    $anomalies[] = $label . ': ' . $kurang;
                }
            }
        }
        
        // 3. Penentuan Status Akhir
        if (!empty($anomalies)) {
            // Gabungkan menjadi: Kondisi: KRITIS (Tuas: Rusak, Plester: -2)
            $detailText = implode(', ', $anomalies);
            return "Kondisi: KRITIS ({$detailText})";
        }

        // Jika array $anomalies kosong (artinya 100% jawaban cocok dengan standar)
        return "Kondisi: BAIK";
    }
}