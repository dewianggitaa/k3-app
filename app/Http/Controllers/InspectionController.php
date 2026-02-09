<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia; 
use Carbon\Carbon;

class InspectionController extends Controller
{
    /**
     * Menampilkan Halaman Utama
     * Logic dipisah berdasarkan Permission User
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->can('manage_inspections')) {
            
            // Query Dasar
            $query = Inspection::with(['assetable', 'executor', 'schedule']);

            // 1. Filter Status (Opsional)
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // 2. Filter Bulan (Default: Bulan Ini)
            // Format input: "2026-02"
            $month = $request->input('month', now()->format('Y-m'));
            $query->where('schedule_date', 'like', "{$month}%");

            // Ambil Data dengan Pagination
            $inspections = $query->latest('due_date')
                                 ->paginate(20)
                                 ->withQueryString();

            // Render Tampilan Admin
            return Inertia::render('Inspections/AdminIndex', [
                'inspections' => $inspections,
                'filters'     => $request->all(['status', 'month']), // Supaya filter tidak reset di frontend
            ]);
        }


        $tasks = Inspection::with(['assetable', 'schedule'])
                    ->where('status', 'pending')
                    ->orderBy('due_date', 'asc')
                    ->get()
                    ->map(function ($task) {
                        return [
                            'id' => $task->id,
                            'asset_name' => class_basename($task->assetable_type) . ' #' . $task->assetable_id,
                            'location'   => $task->assetable->location ?? '-',
                            'due_date'   => $task->due_date->format('Y-m-d'),
                            'due_human'  => $task->due_date->diffForHumans(),
                            'status'     => $task->status,
                        ];
                    });

        // B. History Saya (5 Terakhir)
        $myHistory = Inspection::with(['assetable'])
                    ->where('completed_by', Auth::id())
                    ->latest('completed_at')
                    ->limit(5)
                    ->get();

        // Render Tampilan Petugas
        return Inertia::render('Inspections/Index', [
            'tasks'     => $tasks,
            'myHistory' => $myHistory
        ]);
    }

    public function show($id)
    {
        $inspection = Inspection::with('assetable')->findOrFail($id);

        // Validasi: Kalau sudah selesai, jangan dikerjakan lagi
        if ($inspection->status == 'completed') {
            return to_route('inspections.index')
                 ->with('error', 'Tugas ini sudah selesai.');
        }

        return Inertia::render('Inspections/Show', [
            'inspection' => $inspection,
            'asset_type' => class_basename($inspection->assetable_type),
        ]);
    }

    public function update(Request $request, $id)
    {
        $inspection = Inspection::findOrFail($id);

        // Validasi Race Condition
        if ($inspection->status == 'completed') {
            return back()->with('error', 'Sudah diambil orang lain.');
        }

        // Logic Upload File & Input Data
        $inputs = $request->except(['_token', '_method']);
        $reportData = [];

        foreach ($inputs as $key => $value) {
            if ($request->hasFile($key)) {
                $path = $request->file($key)->store('inspections', 'public');
                $reportData[$key] = [
                    'type' => 'file',
                    'url'  => Storage::url($path)
                ];
            } else {
                $reportData[$key] = $value;
            }
        }

        $inspection->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'completed_by' => Auth::id(),
            'report_data'  => $reportData,
        ]);

        return to_route('inspections.index')->with('success', 'Laporan Terkirim!');
    }
}