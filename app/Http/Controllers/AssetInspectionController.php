<?php

namespace App\Http\Controllers;

use App\Models\Apar;
use App\Models\Hydrant;
use App\Models\P3k;
use App\Models\ChecklistParameter;
use App\Models\P3kTypeItem; 
use Illuminate\Http\Request;

class AssetInspectionController extends Controller
{
    public function getLatest($type, $id)
    {
        $results = [];

        if ($type === 'apar') {
            $asset = Apar::with('latest_inspection')->find($id);
            if (!$asset) return response()->json(['results' => []], 404);

            $parameters = ChecklistParameter::where('asset_type', 'App\Models\Apar')->get();
            $reportData = $asset->latest_inspection ? $asset->latest_inspection->report_data : null;

            foreach ($parameters as $param) {
                $answer = $param->standard_value; 
                $isSafe = true; 
                $isAnsweredInReport = false;

                if ($reportData && isset($reportData['answers'][$param->id])) {
                    $inspectionResponse = $reportData['answers'][$param->id]['response'];
                    
                    if (!empty($inspectionResponse)) {
                        $answer = $inspectionResponse;
                        $isAnsweredInReport = true;
                        
                        if ($param->standard_value !== null) {
                            $isSafe = (strtolower(trim($answer)) === strtolower(trim($param->standard_value)));
                        }
                    }
                }
                
                $labelLower = strtolower($param->label ?? '');

                if (str_contains($labelLower, 'status kadaluarsa') || str_contains($labelLower, 'masa berlaku')) {
                    
                    if (!$isAnsweredInReport) {
                        if (!empty($asset->expired_at)) {
                            $isPast = \Carbon\Carbon::parse($asset->expired_at)->isPast();
                            
                            if ($isPast) {
                                $answer = 'Sudah Kadaluarsa';
                                $isSafe = false;
                            } else {
                                $answer = $param->standard_value ?? 'Belum Kadaluarsa';
                                $isSafe = true;
                            }
                        } else {
                            $answer = 'Data Tanggal Kosong';
                            $isSafe = false;
                        }
                    }
                    
                } 
                elseif (str_contains($labelLower, 'tanggal kadaluarsa')) {
                    
                    if (!$isAnsweredInReport) {
                        if (!empty($asset->expired_at)) {
                            $expiredDate = \Carbon\Carbon::parse($asset->expired_at);
                            $answer = $expiredDate->format('d M Y'); 
                            $isSafe = $expiredDate->isFuture();
                        } else {
                            $answer = 'Belum diset';
                            $isSafe = false;
                        }
                    }
                }
                

                $results[] = [
                    'question' => $param->label, 
                    'answer' => $answer, 
                    'is_safe' => $isSafe
                ];
            }
        } 

        elseif ($type === 'hydrant') {
            $asset = Hydrant::with('latest_inspection')->find($id);
            if (!$asset) return response()->json(['results' => []], 404);

            $parameters = ChecklistParameter::where('asset_type', 'App\Models\Hydrant')->get();
            $reportData = $asset->latest_inspection ? $asset->latest_inspection->report_data : null;

            foreach ($parameters as $param) {
                $answer = $param->standard_value;
                $isSafe = true;

                if ($reportData && isset($reportData['answers'][$param->id])) {
                    $inspectionResponse = $reportData['answers'][$param->id]['response'];
                    
                    if (!empty(trim($inspectionResponse)) && trim($inspectionResponse) !== '-') {
                        $answer = $inspectionResponse;
                        
                        if ($param->standard_value !== null) {
                            $isSafe = (strtolower(trim($answer)) === strtolower(trim($param->standard_value)));
                        }
                    }
                }

                $results[] = [
                    'question' => $param->label,
                    'answer' => $answer ?? '-',
                    'is_safe' => $isSafe
                ];
            }
        }

        elseif ($type === 'p3k') {
            $asset = P3k::with('latest_inspection')->find($id);
            if (!$asset) return response()->json(['results' => []], 404);

            $parameters = P3kTypeItem::with('item')->where('p3k_type_id', $asset->p3k_type_id)->get();
            $reportData = $asset->latest_inspection ? $asset->latest_inspection->report_data : null;

            foreach ($parameters as $param) {
                $answer = $param->standard; 
                $isSafe = true;

                if ($reportData && isset($reportData['answers'][$param->id])) {
                    $answer = $reportData['answers'][$param->id]['response'];
                    $isSafe = ($answer >= $param->standard); 
                }

                $results[] = [
                    'question' => $param->item->name ?? 'Nama Item Kosong', 
                    'answer' => $answer,
                    'is_safe' => $isSafe
                ];
            }
        }

        return response()->json([
            'results' => $results
        ]);
    }
}