<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\ChecklistParameter;
use App\Models\P3kInventory;
use App\Models\P3kTypeItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InspectionExecutionController extends Controller
{
   public function edit($id)
    {
        $inspection = Inspection::with(['assetable'])->findOrFail($id);
        $user = Auth::user();

        // 1. Cek Hak Akses (Sama seperti sebelumnya)
        $isMyTask = ($inspection->user_id === $user->id);
        $isOpenForK3 = (is_null($inspection->user_id) && optional($user->department)->name === 'K3');

        if (!$isMyTask && !$isOpenForK3) {
            abort(403, 'Anda tidak memiliki akses untuk mengerjakan inspeksi ini.');
        }

        // 2. Ambil Parameter Checklist Fisik (Yg ada di tabel checklist_parameters)
        // Contoh: "Kondisi Box", "Kunci Box" (tetap ambil dari DB checklist_parameters)
        $assetTypeClass = $inspection->assetable_type; 
        $assetTypeShort = strtolower(class_basename($assetTypeClass)); 
        
        $realParameters = ChecklistParameter::where(function($query) use ($assetTypeClass, $assetTypeShort) {
                $query->where('asset_type', $assetTypeShort)
                      ->orWhere('asset_type', $assetTypeClass);
            })
            ->orderBy('order_index') 
            ->get();

        // 3. LOGIC KHUSUS P3K: INJECT ITEM SEBAGAI PARAMETER
        if ($assetTypeShort === 'p3k' && $inspection->assetable) {
            
            // Ambil data item & jumlah standar dari tabel relasi P3K
            $p3kItems = P3kTypeItem::join('p3k_items', 'p3k_type_items.p3k_item_id', '=', 'p3k_items.id')
                ->where('p3k_type_items.p3k_type_id', $inspection->assetable->p3k_type_id)
                ->select(
                    'p3k_items.name as label',
                    'p3k_items.id as item_id',
                    DB::raw('COALESCE(p3k_type_items.standard, 0) as standard_qty')
                )
                ->get();

            // SULAP DATA: Ubah formatnya jadi mirip object ChecklistParameter
            $virtualParameters = $p3kItems->map(function($item, $index) {
                return (object) [
                    // Kita bikin ID palsu pakai string biar gak bentrok sama ID asli
                    // Frontend Vue kamu support string key kok di answers_map
                    'id'              => 'virtual_item_' . $item->item_id, 
                    'label'           => $item->label,
                    'input_type'      => 'number', // Set otomatis jadi number
                    'standard_qty'    => $item->standard_qty,
                    'related_item_id' => $item->item_id, // Penting buat save quantities
                    'options'         => null,
                    'asset_type'      => 'p3k',
                    'order_index'     => 100 + $index // Taruh di urutan bawah
                ];
            });

            // GABUNGKAN: Parameter Fisik (DB) + Parameter Item (Virtual)
            // Jadi nanti di layar muncul pertanyaan fisik dulu, baru list item
            $parameters = $realParameters->concat($virtualParameters);

        } else {
            // Kalau bukan P3K (misal APAR), pakai parameter biasa
            $parameters = $realParameters;
        }

        // 4. LOAD JAWABAN (Sama seperti sebelumnya)
        $reportData = $inspection->report_data ?? [];
        $existingAnswers = $reportData['answers'] ?? [];

        $formattedExisting = [];
        if (is_array($existingAnswers)) {
            foreach ($existingAnswers as $paramId => $data) {
                $formattedExisting[$paramId] = [
                    'checklist_parameter_id' => $paramId,
                    'response' => $data['response'] ?? null,
                    'notes'    => $data['notes'] ?? null,
                ];
            }
        }

        return Inertia::render('Inspections/Execute', [
            'inspection' => $inspection,
            'asset'      => $inspection->assetable,
            'parameters' => $parameters, // Kirim hasil gabungan
            'existingAnswers' => $formattedExisting,
        ]);
    }

    public function update(Request $request, $id)
    {
        $inspection = Inspection::findOrFail($id);

        if ($inspection->status === 'completed') {
            return back()->with('error', 'Inspeksi ini sudah selesai.');
        }

        $request->validate([
            'answers'                => 'required|array',
            'quantities'             => 'nullable|array',
            'quantities.*.item_id'   => 'required_with:quantities|integer',
            'quantities.*.current_qty' => 'required_with:quantities|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            $assetTypeClass = $inspection->assetable_type;
            $assetTypeShort = strtolower(class_basename((string)$assetTypeClass));
            
            // 1. Update Stok P3K
            if ($assetTypeShort === 'p3k' && $request->has('quantities')) {
                foreach ($request->quantities as $qtyData) {
                    P3kInventory::updateOrCreate(
                        [
                            'p3k_id'      => $inspection->assetable_id,
                            'p3k_item_id' => $qtyData['item_id']
                        ],
                        [
                            'current_qty' => $qtyData['current_qty']
                        ]
                    );
                }
            }

            // 2. Logic Penentuan Status (Hanya Safe atau Critical)
            $calculatedStatus = 'safe';

            $masterParams = ChecklistParameter::where(function($query) use ($assetTypeClass, $assetTypeShort) {
                    $query->where('asset_type', $assetTypeShort)
                          ->orWhere('asset_type', $assetTypeClass);
                })
                ->where('input_type', '!=', 'number') 
                ->get();

            foreach ($masterParams as $param) {
                // PERBAIKAN DISINI: Tambahkan ['response']
                $answerData = $request->answers[$param->id] ?? [];
                $userAnswer = $answerData['response'] ?? null; 
                
                // Debugging (Opsional, boleh dihapus nanti)
                \Log::info("ID: {$param->id} | User: {$userAnswer} | DB: {$param->standard_value}");

                // Logic Perbandingan
                if ($userAnswer && $userAnswer != $param->standard_value) {
                    $calculatedStatus = 'critical'; 
                }
            }

            // Cek Stok P3K (Jika checklist aman, lanjut cek stok)
            if ($calculatedStatus === 'safe' && $assetTypeShort === 'p3k' && $request->has('quantities')) {
                $standards = P3kTypeItem::where('p3k_type_id', $inspection->assetable->p3k_type_id)
                    ->pluck('standard', 'p3k_item_id')
                    ->toArray();

                foreach ($request->quantities as $qtyData) {
                    $itemId = $qtyData['item_id'];
                    $userQty = $qtyData['current_qty'];
                    $minQty = $standards[$itemId] ?? 0;

                    if ($userQty < $minQty) {
                        $calculatedStatus = 'critical';
                        break; // Salah satu kurang, langsung critical
                    }
                }
            }

            // 3. Update Status Aset
            $inspection->assetable->update(['status' => $calculatedStatus]);

            // 4. Update Inspection Report
            $reportPayload = [
                'answers'          => $request->answers,
                'quantities'       => $request->quantities ?? [],
                'condition_result' => $calculatedStatus,
                'completed_at'     => now()->toDateTimeString(),
            ];

            $inspection->update([
                'status'       => 'completed', 
                'user_id'      => Auth::id(),  
                'report_data'  => $reportPayload,
                'completed_at' => now(),       
            ]);

            DB::commit();

            $user = Auth::user();
            $isK3 = optional($user->department)->name === 'K3';

            $route = $isK3 ? 'inspections.open' : 'inspections.my-tasks';
            
            return redirect()->route($route)
                ->with('success', 'Laporan selesai. Status aset sekarang: ' . strtoupper($calculatedStatus));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }
}