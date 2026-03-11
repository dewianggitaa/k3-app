<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\Apar;
use App\Models\Hydrant;
use App\Models\P3k;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class ScanController extends Controller
{
    public function handleNfc($assetCode)
    {
        $codesToSearch = [$assetCode];
        
        if (str_contains($assetCode, '-')) {
            $parts = explode('-', $assetCode);
            $codesToSearch[] = end($parts);
        }

        $asset = null;
        $assetType = '';
        
        foreach ([Hydrant::class, Apar::class, P3k::class] as $model) {
            $check = $model::whereIn('code', $codesToSearch)->first();
            if ($check) { 
                $asset = $check; 
                $assetType = $model; 
                break; 
            }
        }

        if (!$asset) {
            return redirect()->route('dashboard')->with('error', "Aset tidak terdaftar.");
        }

        if ($assetType === P3k::class) {
            return redirect()->route('p3k.menu', ['id' => $asset->id]);
        }


        if (!Auth::check()) {
            return redirect()->guest(route('login'))->with('warning', 'Anda harus login untuk mengakses data ini.');
        }

        $user = Auth::user();
        $isK3Department = optional($user->department)->name === 'K3';
        $hasK3Role = $user->hasRole('executor_k3');

        $inspection = Inspection::where('assetable_type', $assetType)
            ->where('assetable_id', $asset->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->where(function($query) use ($user, $isK3Department, $hasK3Role) {
                // Anyone can see their own assigned tasks
                $query->where('user_id', $user->id);
                
                // K3 users can also see unassigned tasks
                if ($isK3Department || $hasK3Role) {
                    $query->orWhereNull('user_id');
                }
            })
            ->orderByRaw("FIELD(status, 'overdue', 'pending') ASC")
            ->orderBy('schedule_date', 'asc')
            ->first();

        if ($inspection) {
            return redirect()->route('inspections.execute', $inspection->id)
                ->with('success', 'Aset cocok. Silakan isi laporan.');
        }


        return redirect()->route('dashboard')->with('error', "Tidak ada jadwal tugas aktif untuk aset ini.");
    }
}