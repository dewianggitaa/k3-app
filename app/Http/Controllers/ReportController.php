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
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->when($assetCode !== 'all', fn($q) => $q->whereHasMorph('assetable', [$modelClass], fn($q2) => $q2->where('code', $assetCode)))
                ->get();

            $groupedData = $tab === 'apar'
                ? $this->groupAndFormatK3Reports($allInspections, $checklistParams, $itemNames)
                : $this->formatHydrantReports($allInspections, $checklistParams, $itemNames);

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
        $activityType = $request->query('activity_type', 'all'); 

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
            $dataP3k = collect();
            
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
                $dataP3k = $dataP3k->concat($usages);
            }
            
            if (in_array($activityType, ['all', 'inspection'])) {
                $queryInsp = Inspection::with(['user', 'assetable'])
                    ->where('assetable_type', P3k::class)
                    ->whereNotIn('status', ['pending', 'overdue'])
                    ->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                
                if ($assetCode !== 'all') {
                    $queryInsp->whereHasMorph('assetable', [P3k::class], fn($q) => $q->where('code', $assetCode));
                }
                
                $dataInsp = $queryInsp->get()->map(function ($inspection) use ($checklistParams, $itemNames) {
                    return [
                        'record_date' => $inspection->updated_at, 
                        'asset_code' => $inspection->assetable->code ?? '-',
                        'action_type' => 'INSPEKSI RUTIN', 
                        'actor' => $inspection->user->name ?? 'Sistem K3', 
                        'details' => $this->buildDetail($inspection, $checklistParams, $itemNames),
                    ];
                });
                
                $dataP3k = $dataP3k->concat($dataInsp);
            }

            $data = $dataP3k->sortByDesc('record_date')->values();

        } else {
            $modelClass = $tab === 'apar' ? Apar::class : Hydrant::class;
            
            $allInspections = Inspection::with(['user.department', 'assetable'])
                ->where('assetable_type', $modelClass)
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->when($assetCode !== 'all', fn($q) => $q->whereHasMorph('assetable', [$modelClass], fn($q2) => $q2->where('code', $assetCode)))
                ->get();

            $data = $tab === 'apar'
                ? $this->groupAndFormatK3Reports($allInspections, $checklistParams, $itemNames)
                : $this->formatHydrantReports($allInspections, $checklistParams, $itemNames);
        }

        $pdf = Pdf::loadView($pdfView, [
            'data' => $data, 'tab' => strtoupper($tab), 'startDate' => $startDate, 'endDate' => $endDate, 'selectedAsset' => $assetCode, 
            'printedBy' => auth()->user()->name ?? 'Sistem K3',
            'printedByDepartment' => auth()->user()->department->name ?? 'K3',
            'supervisor' => $supervisorName,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('Laporan_K3_' . strtoupper($tab) . '.pdf');
    }

    /**
     * Menghitung key periode bi-monthly dari sebuah tanggal.
     * Periode 1 = Jan-Feb, Periode 2 = Mar-Apr, dst.
     */
    private function getBimonthlyPeriodKey(string $year, int $month): string
    {
        $periodNum = (int) ceil($month / 2);
        return $year . '-P' . $periodNum;
    }

    private function groupAndFormatK3Reports($inspections, $checklistParams, $itemNames)
    {
        // Kelompokkan per aset + per periode bi-monthly (dari schedule_date)
        // Sehingga 1 baris riwayat = 1 siklus inspeksi (PIC bulanan + validasi K3 per 2 bulan)
        $grouped = $inspections->groupBy(function ($inspection) {
            $scheduleDate = $inspection->schedule_date
                ? Carbon::parse($inspection->schedule_date)
                : Carbon::parse($inspection->completed_at);

            $periodKey = $this->getBimonthlyPeriodKey(
                $scheduleDate->format('Y'),
                $scheduleDate->month
            );

            return $inspection->assetable_id . '|' . $periodKey;
        });

        $monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

        return $grouped->map(function ($periodInspections) use ($checklistParams, $itemNames, $monthNames) {
            $asset = $periodInspections->first()->assetable;

            // Hitung label periode dari schedule_date inspeksi pertama
            $firstDate = $periodInspections->first()->schedule_date
                ? Carbon::parse($periodInspections->first()->schedule_date)
                : Carbon::parse($periodInspections->first()->completed_at);

            $year = $firstDate->year;
            $startMonth = ($firstDate->month % 2 === 0) ? $firstDate->month - 1 : $firstDate->month;
            $endMonth   = $startMonth + 1;
            $periodLabel = $monthNames[$startMonth - 1] . ' – ' . $monthNames[$endMonth - 1] . ' ' . $year;

            // Inspeksi PIC: semua inspeksi oleh non-K3 dalam periode ini
            $picReports = $periodInspections
                ->filter(fn($i) => optional(optional($i->user)->department)->name !== 'K3')
                ->sortBy('completed_at')
                ->map(function ($pic) use ($checklistParams, $itemNames) {
                    $detailString = $this->buildDetail($pic, $checklistParams, $itemNames);
                    $reportData   = $pic->report_data ?? [];

                    // Gunakan condition_result yang disimpan saat inspeksi, bukan status aset live
                    $conditionResult = $reportData['condition_result'] ?? null;
                    $status = $conditionResult === 'critical' ? 'KRITIS' : (
                        str_contains($detailString, 'KRITIS') ? 'KRITIS' : 'SAFE'
                    );

                    return [
                        'id'          => $pic->id,
                        'actor'       => optional($pic->user)->name ?? '-',
                        'record_date' => $pic->completed_at,
                        'status'      => $status,
                        'details'     => $detailString,
                        'notes'       => $reportData['notes'] ?? null,
                    ];
                })->values();

            // Validasi K3: ambil yang terbaru dalam periode ini
            $k3Report = $periodInspections
                ->filter(fn($i) => optional(optional($i->user)->department)->name === 'K3')
                ->sortByDesc('completed_at')
                ->first();

            $reportDataK3 = $k3Report ? ($k3Report->report_data ?? []) : [];

            // Status penggantian tabung: ada parameter 'date' yang diisi K3?
            $statusPenggantian = '-';
            if (isset($reportDataK3['answers']) && is_array($reportDataK3['answers'])) {
                foreach ($reportDataK3['answers'] as $paramId => $ans) {
                    $val = is_array($ans) ? ($ans['response'] ?? null) : $ans;
                    if (isset($checklistParams[$paramId]) && $checklistParams[$paramId]->input_type === 'date' && !empty($val)) {
                        $statusPenggantian = 'Diganti';
                        break;
                    }
                }
            }

            // kondisi_akhir: dari condition_result yang tersimpan di inspeksi K3,
            // fallback ke PIC jika K3 belum ada — BUKAN dari status aset live
            $kondisiAkhir = 'SAFE';
            if ($k3Report) {
                $k3Condition  = $reportDataK3['condition_result'] ?? null;
                $kondisiAkhir = $k3Condition === 'critical' ? 'KRITIS' : 'SAFE';
            } elseif ($picReports->isNotEmpty()) {
                $kondisiAkhir = $picReports->contains('status', 'KRITIS') ? 'KRITIS' : 'SAFE';
            }

            if ($picReports->isEmpty()) {
                $picReports->push([
                    'id'          => 'dummy_' . uniqid(),
                    'actor'       => '-',
                    'record_date' => null,
                    'status'      => '-',
                    'details'     => '',
                    'notes'       => null,
                ]);
            }

            return [
                'id'                 => $k3Report->id ?? 'period_' . $periodInspections->first()->assetable_id . '_' . uniqid(),
                'asset_code'         => $asset->code ?? '-',
                'periode_pemeriksaan'=> $periodLabel,
                'actor_k3'           => optional($k3Report)->user->name ?? 'Belum Divalidasi K3',
                'record_date_k3'     => $k3Report ? $k3Report->completed_at : null,
                'tindakan'           => $reportDataK3['notes'] ?? $reportDataK3['admin_notes'] ?? '-',
                'kondisi_akhir'      => $kondisiAkhir,
                'pic_reports'        => $picReports->toArray(),
                'status_penggantian' => $statusPenggantian,
            ];
        })->values()->sortByDesc('record_date_k3');
    }

    private function formatHydrantReports($inspections, $checklistParams, $itemNames)
    {
        $monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

        return $inspections->sortByDesc('completed_at')->map(function ($inspection) use ($checklistParams, $itemNames, $monthNames) {
            $asset = $inspection->assetable;

            $scheduleDate = $inspection->schedule_date
                ? Carbon::parse($inspection->schedule_date)
                : Carbon::parse($inspection->completed_at);

            $periodLabel = $monthNames[$scheduleDate->month - 1] . ' ' . $scheduleDate->year;

            $detailString = $this->buildDetail($inspection, $checklistParams, $itemNames);
            $reportData = $inspection->report_data ?? [];
            $conditionResult = $reportData['condition_result'] ?? null;

            $status = $conditionResult === 'critical' ? 'KRITIS' : (
                str_contains($detailString, 'KRITIS') ? 'KRITIS' : 'SAFE'
            );

            return [
                'id'                  => $inspection->id,
                'asset_code'          => $asset->code ?? '-',
                'periode_pemeriksaan' => $periodLabel,
                'actor'               => optional($inspection->user)->name ?? '-',
                'record_date'         => $inspection->completed_at,
                'status'              => $status,
                'details'             => $detailString,
                'notes'               => $reportData['notes'] ?? $reportData['admin_notes'] ?? null,
                'kondisi_akhir'       => $status, 
            ];
        })->values();
    }

    private function buildDetail($inspection, $checklistParams, $itemNames)
    {
        $report = is_string($inspection->report_data) ? json_decode($inspection->report_data, true) : $inspection->report_data;
        $anomalies = [];

        if (isset($report['answers']) && is_array($report['answers'])) {
            foreach ($report['answers'] as $paramId => $ans) {
                $userAnswer = is_array($ans) ? ($ans['response'] ?? null) : $ans;
                
                if (isset($checklistParams[$paramId])) {
                    $param = $checklistParams[$paramId];
                    
                    if (($param->input_type ?? '') === 'number') {
                        continue;
                    }

                    $standardVal = $param->standard_value ?? '';

                    if (trim($standardVal) === '') {
                        continue;
                    }

                    if ($userAnswer && trim($userAnswer) != trim($standardVal)) {
                        $label = $param->label ?? $param->name ?? 'Komponen';
                        $anomalies[] = $label . ': ' . $userAnswer;
                    }
                }
            }
        }
        
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