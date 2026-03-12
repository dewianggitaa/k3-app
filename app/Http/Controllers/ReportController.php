<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\Apar;
use App\Models\Hydrant;
use App\Models\P3k;
use App\Models\User;
use App\Models\ReportDocumentVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::user()->can('view-reports'), 403, 'Anda tidak memiliki izin melihat laporan.');

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
        abort_unless(Auth::user()->can('export-reports'), 403, 'Anda tidak memiliki izin export laporan.');

        $tab = $request->query('tab', 'p3k');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $assetCode = $request->query('asset_code', 'all'); 
        $activityType = $request->query('activity_type', 'all'); 

        $checklistParams = DB::table('checklist_parameters')->get()->keyBy('id')->toArray();
        $itemNames = DB::table('p3k_items')->pluck('name', 'id')->toArray();

        $pdfView = 'pdf.' . $tab;
        $data = collect();
        $roomName = '';
        $yearRange = '';

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
            $rawData = $this->formatP3kPdfData($startDate, $endDate, $assetCode, $activityType, $checklistParams, $itemNames);

            if ($assetCode !== 'all') {
                $p3kModel = P3k::with('room')->where('code', $assetCode)->first();
                $roomName = $p3kModel?->room?->name ?? '';
            }

            $hasInspections = $rawData->contains('entry_type', 'inspeksi');
            if ($hasInspections) {
                $documentVersion = ReportDocumentVersion::getActiveForType($tab);
                $inspPageMap = $this->discoverP3kPageBreaks($rawData, [
                    'tab' => strtoupper($tab), 'startDate' => $startDate, 'endDate' => $endDate,
                    'selectedAsset' => $assetCode,
                    'printedBy' => auth()->user()->name ?? 'Sistem K3',
                    'printedByDepartment' => auth()->user()->department->name ?? 'K3',
                    'supervisor' => $supervisorName,
                    'documentVersion' => $documentVersion,
                    'p3kInventory' => collect(),
                    'roomName' => $roomName ?? '',
                    'isPreview' => true,
                ]);
                $data = $this->groupInspectionsByPage($rawData, $inspPageMap);
            } else {
                $data = $rawData;
            }
        } elseif ($tab === 'apar') {
            $aparFormatted = $this->formatAparPdfData($startDate, $endDate, $assetCode);
            $rawData = $aparFormatted['data'];
            $checklistCols = $aparFormatted['columns'];

            $documentVersion = ReportDocumentVersion::getActiveForType($tab);
            if ($assetCode !== 'all') {
                $apar = Apar::with('room')->where('code', $assetCode)->first();
                $roomName = $apar?->room?->name ?? '';
            }
            $startYear = Carbon::parse($startDate)->year;
            $endYear = Carbon::parse($endDate)->year;
            $yearRange = $startYear === $endYear ? (string) $startYear : "$startYear – $endYear";

            $pageNumbers = $this->discoverAparPageBreaks($rawData, [
                'tab' => strtoupper($tab), 'startDate' => $startDate, 'endDate' => $endDate,
                'selectedAsset' => $assetCode,
                'printedBy' => auth()->user()->name ?? 'Sistem K3',
                'printedByDepartment' => auth()->user()->department->name ?? 'K3',
                'supervisor' => $supervisorName,
                'documentVersion' => $documentVersion,
                'roomName' => $roomName ?? '', 'yearRange' => $yearRange,
                'checklistCols' => $checklistCols,
                'isPreview' => true,
            ]);
            $data = $this->splitAparGroupsByPage($rawData, $pageNumbers);
        } else {
            $allInspections = Inspection::with(['user.department', 'assetable'])
                ->where('assetable_type', Hydrant::class)
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->when($assetCode !== 'all', fn($q) => $q->whereHasMorph('assetable', [Hydrant::class], fn($q2) => $q2->where('code', $assetCode)))
                ->get();

            $hydrantFormatted = $this->formatHydrantPdfData($allInspections);
            $data = $hydrantFormatted['data'];
            $checklistCols = $hydrantFormatted['columns'];

            if ($assetCode !== 'all') {
                $hydrant = Hydrant::with('room')->where('code', $assetCode)->first();
                $roomName = $hydrant?->room?->name ?? '';
            }
            $startYear = Carbon::parse($startDate)->year;
            $endYear = Carbon::parse($endDate)->year;
            $yearRange = $startYear === $endYear ? (string) $startYear : "$startYear – $endYear";
        }

        $documentVersion = ReportDocumentVersion::getActiveForType($tab);

        // P3K needs extra data: inventory for "Jumlah Awal"
        $p3kInventory = collect();
        if ($tab === 'p3k' && $assetCode !== 'all') {
            $p3k = P3k::where('code', $assetCode)->first();
            if ($p3k) {
                $p3kInventory = DB::table('p3k_inventories')
                    ->join('p3k_items', 'p3k_inventories.p3k_item_id', '=', 'p3k_items.id')
                    ->where('p3k_inventories.p3k_id', $p3k->id)
                    ->select('p3k_items.id', 'p3k_items.name', 'p3k_inventories.current_qty')
                    ->orderBy('p3k_items.id')
                    ->get();
            }
        }

        $orientation = $tab === 'apar' ? 'portrait' : 'landscape';

        $pdf = Pdf::loadView($pdfView, [
            'data' => $data, 'tab' => strtoupper($tab), 'startDate' => $startDate, 'endDate' => $endDate, 'selectedAsset' => $assetCode, 
            'printedBy' => auth()->user()->name ?? 'Sistem K3',
            'printedByDepartment' => auth()->user()->department->name ?? 'K3',
            'supervisor' => $supervisorName,
            'documentVersion' => $documentVersion,
            'p3kInventory' => $p3kInventory,
            'roomName' => $roomName ?? '',
            'yearRange' => $yearRange ?? '',
            'checklistCols' => $checklistCols ?? collect(),
        ])->setPaper('a4', $orientation);

        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'asset_type' => strtoupper($tab),
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'asset_code' => $assetCode !== 'all' ? $assetCode : 'Semua Aset',
            ])
            ->useLog('aktivitas-sistem')
            ->log('Export/Print laporan PDF: ' . strtoupper($tab) . ' (' . $startDate . ' s/d ' . $endDate . ')');

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

    private function formatHydrantPdfData($inspections)
    {
        $checklistCols = \App\Models\ChecklistParameter::where(function($query) {
                $query->where('asset_type', 'hydrant')
                      ->orWhere('asset_type', Hydrant::class);
            })
            ->orderBy('order_index')
            ->get();

        $dayNames   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

        $result = $inspections->sortBy('completed_at')->map(function ($inspection) use ($checklistCols, $dayNames) {
            $asset = $inspection->assetable;

            $report  = is_string($inspection->report_data) ? json_decode($inspection->report_data, true) : ($inspection->report_data ?? []);
            $answers = $report['answers'] ?? [];

            $completedAt = Carbon::parse($inspection->completed_at);
            $dateStr     = $dayNames[$completedAt->dayOfWeek] . ', ' . $completedAt->format('d/m/Y');

            $dept = optional(optional($inspection->user)->department)->name ?? '';
            $role = strtoupper($dept) === 'K3' ? 'K3' : 'PIC';

            $dynamicAnswers = [];
            foreach ($checklistCols as $col) {
                $dynamicAnswers[] = [
                    'label'  => $col->label,
                    'status' => $this->checkParamMS($answers, $col),
                ];
            }

            return [
                'id'              => $inspection->id,
                'asset_code'      => $asset->code ?? '-',
                'tanggal'         => $dateStr,
                'dynamic_answers' => $dynamicAnswers,
                'petugas'         => (optional($inspection->user)->name ?? '-') . ' (' . $role . ')',
                'keterangan'      => $report['notes'] ?? '',
            ];
        })->values();

        return [
            'data'    => $result,
            'columns' => $checklistCols
        ];
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

    private function buildP3kDetail($inspection, $checklistParams, $itemNames, $assetInv)
    {
        $report = is_string($inspection->report_data) ? json_decode($inspection->report_data, true) : $inspection->report_data;
        $anomalies = [];

        if (isset($report['answers']) && is_array($report['answers'])) {
            foreach ($report['answers'] as $paramId => $ans) {
                $userAnswer = is_array($ans) ? ($ans['response'] ?? null) : $ans;
                if (isset($checklistParams[$paramId])) {
                    $param = $checklistParams[$paramId];
                    if (($param->input_type ?? '') === 'number') continue;
                    $standardVal = $param->standard_value ?? '';
                    if (trim($standardVal) === '') continue;
                    if ($userAnswer && trim($userAnswer) != trim($standardVal)) {
                        $label = $param->label ?? $param->name ?? 'Komponen';
                        $anomalies[] = "• " . $label . ": " . $userAnswer;
                    }
                }
            }
        }

        if (isset($report['quantities']) && is_array($report['quantities'])) {
            foreach ($report['quantities'] as $qtyData) {
                if (is_array($qtyData) && isset($qtyData['item_id'])) {
                    $itemId = $qtyData['item_id'];
                    $actual = (int) ($qtyData['current_qty'] ?? 0);
                    $invItem = $assetInv->firstWhere('p3k_item_id', $itemId);
                    $standard = $invItem ? (int) $invItem->standard : 0;
                    if ($actual < $standard) {
                        $label = $itemNames[$itemId] ?? 'Item ' . $itemId;
                        $anomalies[] = "• " . $label . ": " . $actual . " (Standar: " . $standard . ")";
                    }
                }
            }
        }

        if (!empty($anomalies)) return "Kondisi: KRITIS\nRincian:\n" . implode("\n", $anomalies);
        return "Kondisi: BAIK";
    }

    /**
     * Format data P3K untuk PDF: item-based entries.
     * Sorted ascending by date (terlama di atas).
     */
    private function formatP3kPdfData($startDate, $endDate, $assetCode, $activityType, $checklistParams, $itemNames)
    {
        $entries = collect();

        // ─── Usages (Pemakaian & Penambahan) ─────────────────
        if (in_array($activityType, ['all', 'usage'])) {
            $usages = DB::table('p3k_usages')
                ->join('p3ks', 'p3k_usages.p3k_id', '=', 'p3ks.id')
                ->join('p3k_items', 'p3k_usages.p3k_item_id', '=', 'p3k_items.id')
                ->leftJoin('departments', 'p3k_usages.department_id', '=', 'departments.id')
                ->whereBetween('p3k_usages.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->when($assetCode !== 'all', fn($q) => $q->where('p3ks.code', $assetCode))
                ->select(
                    'p3k_usages.id', 'p3k_usages.created_at as record_date',
                    'p3k_usages.type', 'p3k_usages.reporter_name',
                    'departments.name as department_name',
                    'p3k_items.id as item_id', 'p3k_items.name as item_name',
                    'p3k_usages.qty', 'p3k_usages.notes'
                )
                ->get();

            foreach ($usages as $usage) {
                $isRestock = $usage->type === 'in';
                $actorLabel = $isRestock ? '(K3)' : ($usage->department_name ? "({$usage->department_name})" : '');

                $entries->push([
                    'entry_type' => $isRestock ? 'penambahan' : 'pemakaian',
                    'record_date' => $usage->record_date,
                    'action_type' => $isRestock ? 'Penambahan' : 'Pemakaian',
                    'actor' => $usage->reporter_name . ' ' . $actorLabel,
                    'notes' => $usage->notes ?? '',
                    'items' => [
                        [
                            'item_id' => $usage->item_id,
                            'item_name' => $usage->item_name,
                            'qty' => (int) $usage->qty,
                        ]
                    ],
                ]);
            }
        }

        // ─── Inspeksi Rutin ──────────────────────────────────
        if (in_array($activityType, ['all', 'inspection'])) {
            $queryInsp = Inspection::with(['user.department', 'assetable'])
                ->where('assetable_type', P3k::class)
                ->whereNotIn('status', ['pending', 'overdue'])
                ->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

            if ($assetCode !== 'all') {
                $queryInsp->whereHasMorph('assetable', [P3k::class], fn($q) => $q->where('code', $assetCode));
            }

            $inspections = $queryInsp->get();

            foreach ($inspections as $inspection) {
                $report = is_string($inspection->report_data)
                    ? json_decode($inspection->report_data, true)
                    : $inspection->report_data;

                // Determine actor label: K3 or PIC
                $userDept = optional(optional($inspection->user)->department)->name ?? '';
                $actorLabel = strtoupper($userDept) === 'K3' ? '(K3)' : '(PIC)';
                $actorName = optional($inspection->user)->name ?? 'Sistem K3';

                // Build detail/keterangan from checklist answers
                $detailLines = [];
                $hasIssue = false;
                if (isset($report['answers']) && is_array($report['answers'])) {
                    foreach ($report['answers'] as $paramId => $ans) {
                        $userAnswer = is_array($ans) ? ($ans['response'] ?? null) : $ans;
                        if (isset($checklistParams[$paramId])) {
                            $param = $checklistParams[$paramId];
                            if (($param->input_type ?? '') === 'number') continue;
                            $standardVal = $param->standard_value ?? '';
                            if (trim($standardVal) === '') continue;
                            if ($userAnswer && trim($userAnswer) != trim($standardVal)) {
                                $label = $param->label ?? $param->name ?? 'Komponen';
                                $detailLines[] = $label . ': ' . $userAnswer;
                                $hasIssue = true;
                            }
                        }
                    }
                }

                $keterangan = $hasIssue
                    ? "KRITIS — " . implode(', ', $detailLines)
                    : "Kondisi: BAIK";

                // Build items list with actual quantities from report
                $items = [];
                $quantitiesMap = [];
                if (isset($report['quantities']) && is_array($report['quantities'])) {
                    foreach ($report['quantities'] as $qtyData) {
                        if (is_array($qtyData) && isset($qtyData['item_id'])) {
                            $quantitiesMap[(int) $qtyData['item_id']] = (int) ($qtyData['current_qty'] ?? 0);
                        }
                    }
                }

                // All items sorted by id
                foreach ($itemNames as $itemId => $name) {
                    $items[] = [
                        'item_id' => $itemId,
                        'item_name' => $name,
                        'qty' => $quantitiesMap[$itemId] ?? null,
                    ];
                }

                $entries->push([
                    'entry_type' => 'inspeksi',
                    'record_date' => $inspection->updated_at,
                    'action_type' => 'Inspeksi Rutin',
                    'actor' => $actorName . ' ' . $actorLabel,
                    'notes' => $keterangan,
                    'has_issue' => $hasIssue,
                    'items' => $items,
                ]);
            }
        }

        // Sort ascending by date (terlama di atas, terbaru di bawah)
        return $entries->sortBy('record_date')->values();
    }

    /**
     * Discovery pass: render flat P3K table with inline PHP markers to detect
     * which page each inspection item row lands on. Returns array of page numbers.
     */
    private function discoverP3kPageBreaks($entries, $viewVars)
    {
        $discoveryFile = tempnam(sys_get_temp_dir(), 'p3k_pg_');
        file_put_contents($discoveryFile, '');

        $discoveryVars = array_merge($viewVars, [
            'data' => $entries,
            'discoveryFile' => $discoveryFile,
        ]);

        $html = view('pdf.p3k_discovery', $discoveryVars)->render();

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->getOptions()->set('isPhpEnabled', true);
        $dompdf->getOptions()->set('isRemoteEnabled', true);
        $dompdf->getOptions()->set('chroot', public_path());
        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4', 'landscape');
        $dompdf->render();

        $pageNumbers = [];
        if (file_exists($discoveryFile)) {
            $lines = file($discoveryFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $pageNumbers = array_map('intval', $lines);
            unlink($discoveryFile);
        }

        return $pageNumbers;
    }

    /**
     * Split inspection entries into page-aligned groups.
     * Each group shares the same page → safe for rowspan.
     */
    private function groupInspectionsByPage($entries, $inspectionPageNumbers)
    {
        $result = [];
        $inspRowIdx = 0;

        foreach ($entries as $entry) {
            if ($entry['entry_type'] !== 'inspeksi') {
                $result[] = $entry;
                continue;
            }

            // Group items by page number
            $currentPage = null;
            $currentGroup = [];

            foreach ($entry['items'] as $item) {
                $page = $inspectionPageNumbers[$inspRowIdx] ?? 1;
                $inspRowIdx++;

                if ($currentPage !== null && $page !== $currentPage) {
                    // Page changed — save current group, start new
                    $result[] = array_merge($entry, [
                        'items' => $currentGroup,
                        'is_page_group' => true,
                    ]);
                    $currentGroup = [];
                }

                $currentGroup[] = $item;
                $currentPage = $page;
            }

            if (!empty($currentGroup)) {
                $result[] = array_merge($entry, [
                    'items' => $currentGroup,
                    'is_page_group' => true,
                ]);
            }
        }

        return collect($result);
    }

    // ─── PIC REPORT ────────────────────────────────────────────────────────────

    public function picIndex(Request $request)
    {
        abort_unless(Auth::user()->can('view-pic-reports'), 403, 'Anda tidak memiliki izin melihat laporan PIC.');

        $tab       = $request->query('tab', 'p3k');
        $year      = (int) $request->query('year', now()->year);
        $month     = $request->query('month', 'all');
        $assetCode = $request->query('asset_code', 'all');
        $search    = $request->query('search', '');

        if ($month === 'all') {
            $startDate = Carbon::create($year, 1, 1)->startOfYear()->toDateTimeString();
            $endDate   = Carbon::create($year, 12, 31)->endOfYear()->toDateTimeString();
        } else {
            $startDate = Carbon::create($year, (int) $month, 1)->startOfMonth()->toDateTimeString();
            $endDate   = Carbon::create($year, (int) $month, 1)->endOfMonth()->toDateTimeString();
        }

        $assetsList = [
            'p3k'  => P3k::select('id', 'code')->orderBy('code')->get(),
            'apar' => Apar::select('id', 'code')->orderBy('code')->get(),
        ];

        $data = collect();

        if ($tab === 'p3k') {
            $checklistParams = DB::table('checklist_parameters')->get()->keyBy('id')->toArray();
            $itemNames       = DB::table('p3k_items')->pluck('name', 'id')->toArray();

            // PIC inspections (non-K3), eager-load location chain
            $inspections = Inspection::with(['user.department', 'assetable.room.floor.building'])
                ->where('assetable_type', P3k::class)
                ->whereNotIn('status', ['pending', 'overdue'])
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->whereHas('user', fn($q) => $q->whereHas('department', fn($q2) => $q2->where('name', '!=', 'K3')))
                ->when($assetCode !== 'all', fn($q) => $q->whereHasMorph('assetable', [P3k::class], fn($q2) => $q2->where('code', $assetCode)))
                ->when($search !== '', function($q) use ($search) {
                    $q->where(function($query) use ($search) {
                        $query->whereHasMorph('assetable', [P3k::class], function($q2) use ($search) {
                            $q2->where('code', 'like', "%{$search}%");
                        })->orWhereHas('user', function($q3) use ($search) {
                            $q3->where('name', 'like', "%{$search}%");
                        });
                    });
                })
                ->get();

            // PIC pemakaian (type='out') with location info
            $picUsages = DB::table('p3k_usages')
                ->join('p3ks', 'p3k_usages.p3k_id', '=', 'p3ks.id')
                ->join('p3k_items', 'p3k_usages.p3k_item_id', '=', 'p3k_items.id')
                ->leftJoin('users', 'p3k_usages.user_id', '=', 'users.id')
                ->leftJoin('departments', 'departments.id', '=', DB::raw('COALESCE(p3k_usages.department_id, users.department_id)'))
                ->leftJoin('rooms', 'p3ks.room_id', '=', 'rooms.id')
                ->leftJoin('floors', 'rooms.floor_id', '=', 'floors.id')
                ->leftJoin('buildings', 'floors.building_id', '=', 'buildings.id')
                ->where('p3k_usages.type', 'out')
                ->whereBetween('p3k_usages.created_at', [$startDate, $endDate])
                ->when($assetCode !== 'all', fn($q) => $q->where('p3ks.code', $assetCode))
                ->when($search !== '', function($q) use ($search) {
                    $q->where(function($query) use ($search) {
                        $query->where('p3ks.code', 'like', "%{$search}%")
                              ->orWhere('p3k_usages.reporter_name', 'like', "%{$search}%");
                    });
                })
                ->select(
                    'p3k_usages.id', 'p3k_usages.p3k_id as p3k_asset_id',
                    'p3k_usages.qty', 'p3k_usages.notes',
                    'p3k_usages.reporter_name', 'p3k_usages.created_at',
                    'p3ks.code as asset_code',
                    'p3k_items.name as item_name',
                    'departments.name as dept_name',
                    'rooms.name as room_name',
                    'floors.name as floor_name',
                    'buildings.name as building_name'
                )
                ->get();

            // All K3 restocks (type='in' by K3 dept) from start date onwards — for historical status calc
            $k3RestocksQuery = DB::table('p3k_usages')
                ->join('users', 'p3k_usages.user_id', '=', 'users.id')
                ->join('departments', 'users.department_id', '=', 'departments.id')
                ->where('p3k_usages.type', 'in')
                ->where('departments.name', 'K3')
                ->where('p3k_usages.created_at', '>=', $startDate);

            if ($assetCode !== 'all') {
                $k3RestocksQuery->join('p3ks', 'p3k_usages.p3k_id', '=', 'p3ks.id')
                    ->where('p3ks.code', $assetCode);
            }

            $allRestocks = $k3RestocksQuery->select('p3k_usages.p3k_id', 'p3k_usages.created_at')
                ->get()
                ->groupBy('p3k_id');

            // Inventory + standard for low-stock check (current state)
            $inventories = DB::table('p3k_inventories')
                ->join('p3ks', 'p3k_inventories.p3k_id', '=', 'p3ks.id')
                ->join('p3k_type_items', function ($join) {
                    $join->on('p3k_type_items.p3k_item_id', '=', 'p3k_inventories.p3k_item_id')
                         ->on('p3k_type_items.p3k_type_id', '=', 'p3ks.p3k_type_id');
                })
                ->select(
                    'p3k_inventories.p3k_id',
                    'p3k_inventories.p3k_item_id',
                    'p3k_inventories.current_qty',
                    'p3k_type_items.standard'
                )
                ->get()
                ->groupBy('p3k_id');

            $groupedData = $this->buildP3kPicReport($inspections, $picUsages, $allRestocks, $inventories, $checklistParams, $itemNames);

            $page    = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
            $perPage = 20;
            $data    = new \Illuminate\Pagination\LengthAwarePaginator(
                $groupedData->forPage($page, $perPage)->values(),
                $groupedData->count(), $perPage, $page,
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );
            $data->appends($request->all());

        } elseif ($tab === 'apar') {
            $checklistParams = DB::table('checklist_parameters')->get()->keyBy('id')->toArray();
            $itemNames       = DB::table('p3k_items')->pluck('name', 'id')->toArray();

            $allInspections = Inspection::with(['user.department', 'assetable'])
                ->where('assetable_type', Apar::class)
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->when($assetCode !== 'all', fn($q) => $q->whereHasMorph('assetable', [Apar::class], fn($q2) => $q2->where('code', $assetCode)))
                ->when($search !== '', function($q) use ($search) {
                    $q->where(function($query) use ($search) {
                        $query->whereHasMorph('assetable', [Apar::class], function($q2) use ($search) {
                            $q2->where('code', 'like', "%{$search}%");
                        })->orWhereHas('user', function($q3) use ($search) {
                            $q3->where('name', 'like', "%{$search}%");
                        });
                    });
                })
                ->get();

            $groupedData = $this->buildAparPicReport($allInspections, $checklistParams, $itemNames);

            $page    = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
            $perPage = 10;
            $data    = new \Illuminate\Pagination\LengthAwarePaginator(
                $groupedData->forPage($page, $perPage)->values(),
                $groupedData->count(), $perPage, $page,
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );
            $data->appends($request->all());
        }

        return Inertia::render('Reports/PicReport', [
            'activeTab'  => $tab,
            'assetsList' => $assetsList,
            'filters'    => ['year' => $year, 'month' => $month, 'asset_code' => $assetCode, 'search' => $search],
            'reports'    => $data,
        ]);
    }

    private function buildP3kPicReport($inspections, $picUsages, $allRestocks, $inventories, $checklistParams, $itemNames)
    {
        $rows = collect();

        // ── Inspection rows ──────────────────────────────────────────────────
        foreach ($inspections as $insp) {
            $assetId   = $insp->assetable_id;

            $assetInv = $inventories->get((int) $assetId, collect());
            $assetRestocks = $allRestocks->get((int) $assetId, collect());
            $hasRestockAfter = $assetRestocks->contains(fn($r) => Carbon::parse($r->created_at)->gt(Carbon::parse($insp->completed_at)));

            $reportData      = $insp->report_data ?? [];
            $conditionResult = $reportData['condition_result'] ?? null;
            $detailString    = $this->buildP3kDetail($insp, $checklistParams, $itemNames, $assetInv);
            $inspStatus = $conditionResult === 'critical' ? 'KRITIS'
                : (str_contains($detailString, 'KRITIS') ? 'KRITIS' : 'SAFE');
            
            if ($inspStatus === 'KRITIS') {
                $status = $hasRestockAfter ? 'Sudah Ditambah' : 'Belum Ditambah';
            } else {
                $status = 'Aman';
            }

            $asset    = $insp->assetable;
            $room     = $asset?->room;
            $floor    = $room?->floor;
            $building = $floor?->building;
            $location = implode(' › ', array_filter([$building?->name, $floor?->name, $room?->name])) ?: '-';

            $rows->push([
                'id'            => 'insp_' . $insp->id,
                'sort_date'     => $insp->completed_at,
                'event_type'    => 'inspection',
                'asset_code'    => $asset?->code ?? '-',
                'location'      => $location,
                'reporter_name' => optional($insp->user)->name ?? '-',
                'reporter_dept' => optional(optional($insp->user)->department)->name ?? '-',
                'record_date'   => $insp->completed_at,
                'insp_status'   => $inspStatus,
                'details'       => $detailString,
                'items_used'    => [],
                'status'        => $status,
            ]);
        }

        // ── Usage rows (pemakaian, grouped by asset + date + reporter) ───────
        // Multiple items from the same person/date/asset → one row
        $usageGroups = $picUsages->groupBy(
            fn($u) => $u->p3k_asset_id . '|' . Carbon::parse($u->created_at)->format('Y-m-d H') . '|' . $u->reporter_name
        );

        foreach ($usageGroups as $groupKey => $groupUsages) {
            $first     = $groupUsages->first();
            $assetId   = $first->p3k_asset_id;

            $assetRestocks = $allRestocks->get((int) $assetId, collect());
            $hasRestockAfter = $assetRestocks->contains(fn($r) => Carbon::parse($r->created_at)->gt(Carbon::parse($first->created_at)));

            // Low-stock check (current state)
            $assetInv    = $inventories->get((int) $assetId, collect());
            $hasLowStock = $assetInv->contains(fn($inv) => $inv->standard !== null && $inv->current_qty < $inv->standard);
            
            if ($hasRestockAfter) {
                $status = 'Sudah Ditambah';
            } else {
                if ($hasLowStock) {
                    $status = 'Belum Ditambah';
                } else {
                    $status = 'Aman';
                }
            }

            $location = implode(' › ', array_filter([$first->building_name, $first->floor_name, $first->room_name])) ?: '-';

            $rows->push([
                'id'            => 'usage_' . $groupKey,
                'sort_date'     => $first->created_at,
                'event_type'    => 'usage',
                'asset_code'    => $first->asset_code,
                'location'      => $location,
                'reporter_name' => $first->reporter_name,
                'reporter_dept' => $first->dept_name ?? '-',
                'record_date'   => $first->created_at,
                'insp_status'   => null,
                'details'       => null,
                'items_used'    => $groupUsages->map(fn($u) => [
                    'name' => $u->item_name,
                    'qty'  => $u->qty,
                ])->values()->toArray(),
                'status'        => $status,
            ]);
        }

        return $rows->sort(function ($a, $b) {
            if ($a['status'] === 'Belum Ditambah' && $b['status'] !== 'Belum Ditambah') return -1;
            if ($b['status'] === 'Belum Ditambah' && $a['status'] !== 'Belum Ditambah') return 1;
            return $b['sort_date'] <=> $a['sort_date'];
        })->values();
    }

    private function buildAparPicReport($inspections, $checklistParams, $itemNames)
    {
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        $grouped = $inspections->groupBy(function ($inspection) {
            $scheduleDate = $inspection->schedule_date
                ? Carbon::parse($inspection->schedule_date)
                : Carbon::parse($inspection->completed_at);
            $periodKey = $this->getBimonthlyPeriodKey($scheduleDate->format('Y'), $scheduleDate->month);
            return $inspection->assetable_id . '|' . $periodKey;
        });

        return $grouped->map(function ($periodInspections) use ($checklistParams, $itemNames, $monthNames) {
            $asset     = $periodInspections->first()->assetable;
            $firstDate = $periodInspections->first()->schedule_date
                ? Carbon::parse($periodInspections->first()->schedule_date)
                : Carbon::parse($periodInspections->first()->completed_at);

            $year       = $firstDate->year;
            $startMonth = ($firstDate->month % 2 === 0) ? $firstDate->month - 1 : $firstDate->month;
            $endMonth   = $startMonth + 1;
            $periodLabel = $monthNames[$startMonth - 1] . ' – ' . $monthNames[$endMonth - 1] . ' ' . $year;

            // PIC reports (non-K3)
            $picReports = $periodInspections
                ->filter(fn($i) => optional(optional($i->user)->department)->name !== 'K3')
                ->sortBy('completed_at')
                ->map(function ($pic) use ($checklistParams, $itemNames) {
                    $detailString    = $this->buildDetail($pic, $checklistParams, $itemNames);
                    $reportData      = $pic->report_data ?? [];
                    $conditionResult = $reportData['condition_result'] ?? null;
                    $status = $conditionResult === 'critical' ? 'KRITIS'
                        : (str_contains($detailString, 'KRITIS') ? 'KRITIS' : 'SAFE');
                    return [
                        'id'          => $pic->id,
                        'actor'       => optional($pic->user)->name ?? '-',
                        'record_date' => $pic->completed_at,
                        'status'      => $status,
                        'details'     => $detailString,
                    ];
                })->values();

            // K3 validation report (latest in period)
            $k3Report = $periodInspections
                ->filter(fn($i) => optional(optional($i->user)->department)->name === 'K3')
                ->sortByDesc('completed_at')
                ->first();

            $validationStatus = $k3Report ? 'Sudah Divalidasi' : 'Belum Divalidasi';

            // Repair status
            $hasCriticalPic = $picReports->contains('status', 'KRITIS');
            if (! $hasCriticalPic) {
                $repairStatus = 'Aman';
            } elseif ($k3Report) {
                $k3Condition  = ($k3Report->report_data ?? [])['condition_result'] ?? null;
                $repairStatus = $k3Condition === 'critical' ? 'Perlu Perbaikan' : 'Sudah Diperbaiki';
            } else {
                $repairStatus = 'Perlu Perbaikan';
            }

            $firstPicDate = count($picReports) > 0 ? $picReports->first()['record_date'] : null;
            $sortDate = $k3Report?->completed_at ?? $firstPicDate ?? $firstDate;

            $sortGroup = 3;
            if ($repairStatus === 'Perlu Perbaikan') {
                $sortGroup = 1;
            } elseif ($validationStatus === 'Belum Divalidasi') {
                $sortGroup = 2;
            }

            return [
                'id'                  => $k3Report?->id ?? 'period_' . $periodInspections->first()->assetable_id . '_' . uniqid(),
                'asset_code'          => $asset->code ?? '-',
                'periode_pemeriksaan' => $periodLabel,
                'record_date_k3'      => $k3Report?->completed_at,
                'actor_k3'            => optional($k3Report)->user?->name ?? 'Belum Divalidasi K3',
                'pic_reports'         => $picReports->toArray(),
                'validation_status'   => $validationStatus,
                'repair_status'       => $repairStatus,
                'sort_group'          => $sortGroup,
                'sort_date'           => $sortDate,
            ];
        })->values()->sort(function($a, $b) {
            if ($a['sort_group'] !== $b['sort_group']) {
                return $a['sort_group'] <=> $b['sort_group'];
            }
            if ($a['sort_group'] <= 2) {
                return $a['sort_date'] <=> $b['sort_date'];
            } else {
                return $b['sort_date'] <=> $a['sort_date'];
            }
        })->values();
    }

    // ─── APAR PDF FORMATTING ──────────────────────────────────────────────────

    private function formatAparPdfData($startDate, $endDate, $assetCode)
    {
        $monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $dayNames   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

        // Fetch APAR checklist parameters sorted by order_index
        $aparParamsList = \App\Models\ChecklistParameter::where(function($query) {
                $query->where('asset_type', 'apar')
                      ->orWhere('asset_type', Apar::class);
            })
            ->orderBy('order_index')
            ->get();

        $checklistCols = $aparParamsList->where('input_type', '!=', 'date')->values();
        $dateParams    = $aparParamsList->where('input_type', 'date')->values();

        $allInspections = Inspection::with(['user.department', 'assetable'])
            ->where('assetable_type', Apar::class)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->when($assetCode !== 'all', fn($q) => $q->whereHasMorph('assetable', [Apar::class], fn($q2) => $q2->where('code', $assetCode)))
            ->get();

        // Group by opsi + bi-monthly period
        $grouped = $allInspections->groupBy(function ($inspection) {
            $scheduleDate = $inspection->schedule_date
                ? Carbon::parse($inspection->schedule_date)
                : Carbon::parse($inspection->completed_at);
            $periodKey = $this->getBimonthlyPeriodKey($scheduleDate->format('Y'), $scheduleDate->month);
            return $inspection->assetable_id . '|' . $periodKey;
        });

        $result = collect();

        foreach ($grouped as $key => $periodInspections) {
            $asset = $periodInspections->first()->assetable;
            $assetExpiredAt = $asset->expired_at;

            $firstDate = $periodInspections->first()->schedule_date
                ? Carbon::parse($periodInspections->first()->schedule_date)
                : Carbon::parse($periodInspections->first()->completed_at);

            $startMonth = ($firstDate->month % 2 === 0) ? $firstDate->month - 1 : $firstDate->month;
            $endMonth   = $startMonth + 1;
            $periodLabel = $monthNames[$startMonth - 1] . ' – ' . $monthNames[$endMonth - 1];

            $sorted = $periodInspections->sortBy('completed_at');

            $inspectionRows = [];
            foreach ($sorted as $inspection) {
                $report  = is_string($inspection->report_data) ? json_decode($inspection->report_data, true) : ($inspection->report_data ?? []);
                $answers = $report['answers'] ?? [];

                $completedAt = Carbon::parse($inspection->completed_at);
                $dateStr     = $dayNames[$completedAt->dayOfWeek] . ', ' . $completedAt->format('d/m');

                $dept = optional(optional($inspection->user)->department)->name ?? '';
                $role = strtoupper($dept) === 'K3' ? 'K3' : 'PIC';

                $dynamicAnswers = [];
                foreach ($checklistCols as $col) {
                    $dynamicAnswers[] = [
                        'label'  => $col->label,
                        'status' => $this->checkParamMS($answers, $col),
                    ];
                }

                $inspectionRows[] = [
                    'tanggal'         => $dateStr,
                    'dynamic_answers' => $dynamicAnswers,
                    'petugas'         => (optional($inspection->user)->name ?? '-') . ' (' . $role . ')',
                    'keterangan'      => $report['notes'] ?? '',
                ];
            }

            $result->push([
                'periode_label' => $periodLabel,
                'asset_code'    => $asset->code ?? '-',
                'inspections'   => $inspectionRows,
            ]);
        }

        return [
            'data' => $result->sortBy('periode_label')->values(),
            'columns' => $checklistCols
        ];
    }

    private function getAnswerForParam($answers, $param)
    {
        if (!$param) return null;

        $paramId = $param->id;
        $val = is_array($answers[$paramId] ?? null) ? ($answers[$paramId]['response'] ?? null) : ($answers[$paramId] ?? null);
        if (!is_null($val)) return trim($val);

        $options = is_string($param->options) ? json_decode($param->options, true) : ($param->options ?? []);
        if (!empty($options) && is_array($options)) {
            foreach ($answers as $data) {
                $response = is_array($data) ? ($data['response'] ?? null) : $data;
                if ($response !== null && in_array(trim($response), array_map('trim', $options))) {
                    return trim($response);
                }
            }
        }

        if ($param->input_type === 'date') {
            foreach ($answers as $data) {
                $response = is_array($data) ? ($data['response'] ?? null) : $data;
                if ($response !== null && (bool)strtotime(trim($response))) {
                    return trim($response);
                }
            }
        }

        return null;
    }

    private function checkParamMS($answers, $param)
    {
        if (!$param) return '-';
        $answer = $this->getAnswerForParam($answers, $param);
        if (!$answer) return '-';
        return trim($answer) === trim($param->standard_value) ? 'MS' : 'TMS';
    }

    private function checkLabelMS($answers, $labelParam, $masaBerlakuParam, $tglKadaluarsaParam, $assetExpiredAt)
    {
        if (!$labelParam || !$masaBerlakuParam) return '-';

        $a1 = $this->getAnswerForParam($answers, $labelParam);
        $labelOk = $a1 && trim($a1) === trim($labelParam->standard_value);

        $a2 = $this->getAnswerForParam($answers, $masaBerlakuParam);
        $masaOk = $a2 && trim($a2) === trim($masaBerlakuParam->standard_value);

        $tglOk = true;
        if ($tglKadaluarsaParam) {
            $a3 = $this->getAnswerForParam($answers, $tglKadaluarsaParam);
            if ($a3) {
                $tglOk = Carbon::parse($a3)->gte(Carbon::today());
            } else {
                $tglOk = $assetExpiredAt && Carbon::parse($assetExpiredAt)->gte(Carbon::today());
            }
        }

        return ($labelOk && $masaOk && $tglOk) ? 'MS' : 'TMS';
    }

    private function discoverAparPageBreaks($data, $viewVars)
    {
        $discoveryFile = tempnam(sys_get_temp_dir(), 'apar_pg_');
        file_put_contents($discoveryFile, '');

        $discoveryVars = array_merge($viewVars, [
            'data'          => $data,
            'discoveryFile' => $discoveryFile,
        ]);

        $html = view('pdf.apar_discovery', $discoveryVars)->render();

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->getOptions()->set('isPhpEnabled', true);
        $dompdf->getOptions()->set('isRemoteEnabled', true);
        $dompdf->getOptions()->set('chroot', public_path());
        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->render();

        $pageNumbers = [];
        if (file_exists($discoveryFile)) {
            $lines = file($discoveryFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $pageNumbers = array_map('intval', $lines);
            unlink($discoveryFile);
        }
        return $pageNumbers;
    }

    private function splitAparGroupsByPage($data, $pageNumbers)
    {
        $result = collect();
        $rowIdx = 0;

        foreach ($data as $group) {
            $currentPage = null;
            $currentInspections = [];

            foreach ($group['inspections'] as $inspection) {
                $page = $pageNumbers[$rowIdx] ?? 1;
                $rowIdx++;

                if ($currentPage !== null && $page !== $currentPage) {
                    $result->push(array_merge($group, ['inspections' => $currentInspections]));
                    $currentInspections = [];
                }
                $currentInspections[] = $inspection;
                $currentPage = $page;
            }

            if (!empty($currentInspections)) {
                $result->push(array_merge($group, ['inspections' => $currentInspections]));
            }
        }

        return $result;
    }
}