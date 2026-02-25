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

        $isMyTask = ($inspection->user_id === $user->id);
        $isOpenForK3 = (is_null($inspection->user_id) && optional($user->department)->name === 'K3');

        if (!$isMyTask && !$isOpenForK3) {
            abort(403, 'Anda tidak memiliki akses untuk mengerjakan inspeksi ini.');
        }

        $isK3 = optional($user->department)->name === 'K3';

        $assetTypeClass = $inspection->assetable_type; 
        $assetTypeShort = strtolower(class_basename($assetTypeClass)); 
        
        $realParameters = ChecklistParameter::where(function($query) use ($assetTypeClass, $assetTypeShort) {
                $query->where('asset_type', $assetTypeShort)
                      ->orWhere('asset_type', $assetTypeClass);
            })
            ->orderBy('order_index') 
            ->get();
            
        if (!$isK3) {
            $realParameters = $realParameters->reject(function($param) {
                return $param->input_type === 'date';
            })->values();
        }

        if ($assetTypeShort === 'p3k' && $inspection->assetable) {
            
            $p3kItems = P3kTypeItem::join('p3k_items', 'p3k_type_items.p3k_item_id', '=', 'p3k_items.id')
                ->where('p3k_type_items.p3k_type_id', $inspection->assetable->p3k_type_id)
                ->select(
                    'p3k_items.name as label',
                    'p3k_items.id as item_id',
                    DB::raw('COALESCE(p3k_type_items.standard, 0) as standard_qty')
                )
                ->get();

            $virtualParameters = $p3kItems->map(function($item, $index) {
                return (object) [
                    'id'              => 'virtual_item_' . $item->item_id, 
                    'label'           => $item->label,
                    'input_type'      => 'number',
                    'standard_qty'    => $item->standard_qty,
                    'related_item_id' => $item->item_id,
                    'options'         => null,
                    'asset_type'      => 'p3k',
                    'order_index'     => 100 + $index
                ];
            });

            $parameters = $realParameters->concat($virtualParameters);

        } else {
            $parameters = $realParameters;
        }

        $reportData = $inspection->report_data ?? [];
        $existingAnswers = $reportData['answers'] ?? [];

        $formattedExisting = [];
        if (is_array($existingAnswers)) {
            foreach ($existingAnswers as $paramId => $data) {
                $formattedExisting[$paramId] = [
                    'checklist_parameter_id' => $paramId,
                    'response' => $data['response'] ?? null,
                ];
            }
        }

        return Inertia::render('Inspections/Execute', [
            'inspection' => $inspection,
            'asset'      => $inspection->assetable,
            'parameters' => $parameters,
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
            'notes'                  => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $assetTypeClass = $inspection->assetable_type;
            $assetTypeShort = strtolower(class_basename((string)$assetTypeClass));
            
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

            $calculatedStatus = 'safe';

            $masterParams = ChecklistParameter::where(function($query) use ($assetTypeClass, $assetTypeShort) {
                    $query->where('asset_type', $assetTypeShort)
                          ->orWhere('asset_type', $assetTypeClass);
                })
                ->whereNotIn('input_type', ['number', 'date']) 
                ->get();

            foreach ($masterParams as $param) {
                $answerData = $request->answers[$param->id] ?? [];
                $userAnswer = $answerData['response'] ?? null; 
                
                if ($userAnswer && $userAnswer != $param->standard_value) {
                    $calculatedStatus = 'critical'; 
                }
            }

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
                        break;
                    }
                }
            }

            $inspection->assetable->update(['status' => $calculatedStatus]);

            $reportPayload = [
                'answers'          => $request->answers,
                'quantities'       => $request->quantities ?? [],
                'condition_result' => $calculatedStatus,
                'completed_at'     => now()->toDateTimeString(),
                'notes'        => $request->notes,
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