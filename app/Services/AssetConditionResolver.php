<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AssetConditionResolver
{
    public static function resolveChecklistStatus($parameters, $answers)
    {
        foreach ($parameters as $param) {
            // Ambil data jawaban user (bisa array atau null)
            $userAnswerData = $answers[$param->id] ?? null;

            // Pastikan kita mengambil 'response'-nya saja (String), bukan Array utuh
            // Jika formatnya {response: '...', notes: '...'}, kita ambil response
            $userResponse = isset($userAnswerData['response']) ? $userAnswerData['response'] : null;

            // Jika userResponse masih array (kasus aneh), kita skip atau anggap null
            if (is_array($userResponse)) {
                $userResponse = null; 
            }

            // Ambil standar dari DB
            $standard = $param->standard_value; 

            // LOGIC COMPARISON (Tanpa strtolower biar aman, atau pakai strcasecmp)
            // Jika standar ada isinya, dan jawaban user beda -> Maintenance
            if (!empty($standard) && $userResponse !== $standard) {
                return 'maintenance';
            }
        }

        return 'good';
    }

    public static function resolveP3kStatus($p3kTypeId, $submittedQuantities)
    {
        if (empty($submittedQuantities) || !is_array($submittedQuantities)) return 'good';

        $standards = DB::table('p3k_type_items')
            ->where('p3k_type_id', $p3kTypeId)
            ->pluck('standard', 'p3k_item_id'); 

        foreach ($submittedQuantities as $qtyData) {
            // Validasi format data
            if (!isset($qtyData['item_id']) || !isset($qtyData['current_qty'])) continue;

            $itemId = $qtyData['item_id'];
            $currentQty = (int) $qtyData['current_qty'];

            if (isset($standards[$itemId])) {
                $minQty = (int) $standards[$itemId];
                if ($currentQty < $minQty) {
                    return 'maintenance';
                }
            }
        }

        return 'good';
    }
}