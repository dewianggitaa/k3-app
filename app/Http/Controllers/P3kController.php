<?php

namespace App\Http\Controllers;

use App\Models\P3k;
use App\Models\P3kType;
use App\Models\Room;
use App\Models\P3kInventory;
use App\Models\P3kTypeItem;
use App\Models\P3kItem;
use App\Models\Inspection;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class P3kController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->can('view-master-data') && !Auth::user()->can('manage-assets')) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        $query = P3k::query()->with(['type', 'room.floor.building']);

        if ($request->search) {
            $query->where('code', 'like', "%{$request->search}%")
                  ->orWhereHas('type', function ($q) use ($request) {
                      $q->where('name', 'like', "%{$request->search}%");
                  });
        }

        return Inertia::render('MasterData/Assets/P3k/Index', [
            'p3ks'      => $query->latest()->paginate(10)->withQueryString(),
            'p3k_types' => P3kType::all(),
            'rooms'     => Room::with('floor.building')->get(),
            'filters'   => $request->only(['search']),
            'can'       => [
                'manage' => Auth::user()->can('manage-assets'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->can('manage-assets'), 403, 'Anda tidak memiliki izin.');

        $validated = $request->validate([
            'code'          => 'required|string|unique:p3ks',
            'p3k_type_id'   => 'required|exists:p3k_types,id',
            'room_id'       => 'required|exists:rooms,id',
            'status'        => 'required|in:safe,warning,critical',
            'location_data' => 'nullable|array',
        ]);

        P3k::create($validated);
        return redirect()->back()->with('success', 'Kotak P3K berhasil ditambahkan!');
    }

    public function update(Request $request, P3k $p3k)
    {
        abort_unless(Auth::user()->can('manage-assets'), 403, 'Anda tidak memiliki izin.');

        $validated = $request->validate([
            'code'          => 'required|string|unique:p3ks,code,' . $p3k->id,
            'p3k_type_id'   => 'required|exists:p3k_types,id',
            'room_id'       => 'required|exists:rooms,id',
            'status'        => 'required|in:safe,warning,critical',
            'location_data' => 'nullable|array',
        ]);

        $p3k->update($validated);
        return redirect()->back()->with('success', 'Data P3K berhasil diperbarui!');
    }

    public function destroy(P3k $p3k)
    {
        abort_unless(Auth::user()->can('manage-assets'), 403, 'Anda tidak memiliki izin.');

        $p3k->delete();
        return redirect()->back()->with('success', 'Kotak P3K berhasil dihapus!');
    }

    public function menu(Request $request, $id)
    {
        $p3k = P3k::with(['type', 'room.floor.building'])->findOrFail($id);

        $inspection = null;
        if ($request->inspection_id) {
            $found = Inspection::find($request->inspection_id);
            if ($found && in_array($found->status, ['pending', 'overdue'])) {
                $inspection = $found;
            }
        } 
        
        if (!$inspection) {
            $query = Inspection::where('assetable_type', P3k::class)
                ->where('assetable_id', $p3k->id)
                ->whereIn('status', ['pending', 'overdue'])
                ->orderByRaw("FIELD(status, 'overdue', 'pending') ASC")
                ->orderBy('schedule_date', 'asc');
                
            if (Auth::check()) {
                $user = Auth::user();
                $inspection = (clone $query)->where('user_id', $user->id)->first();
                
                if (!$inspection) {
                    $isK3Department = optional($user->department)->name === 'K3';
                    $hasK3Role = $user->hasRole('executor_k3');
                    if ($isK3Department || $hasK3Role) {
                        $inspection = (clone $query)->whereNull('user_id')->first();
                    }
                }
            }
            
            if (!$inspection) {
                $inspection = $query->first();
            }
        }

        return Inertia::render('P3k/Menu', [
            'asset'      => $p3k,
            'inspection' => $inspection,
        ]);
    }
    
    public function executePending($id)
    {
        $p3k = P3k::findOrFail($id);
        
        $query = Inspection::where('assetable_type', P3k::class)
            ->where('assetable_id', $p3k->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderByRaw("FIELD(status, 'overdue', 'pending') ASC")
            ->orderBy('schedule_date', 'asc');
            
        $user = Auth::user();
        $inspection = (clone $query)->where('user_id', $user->id)->first();
        
        if (!$inspection) {
            $isK3Department = optional($user->department)->name === 'K3';
            $hasK3Role = $user->hasRole('executor_k3');
            if ($isK3Department || $hasK3Role) {
                $inspection = (clone $query)->whereNull('user_id')->first();
            }
        }
        
        if (!$inspection) {
            $inspection = $query->first(); // Absolute fallback
        }
        
        if ($inspection) {
            return redirect()->route('inspections.execute', $inspection->id);
        }
        
        return redirect()->route('p3k.menu', $id)->with('error', 'Tidak ada inspeksi aktif untuk Anda.');
    }

    public function createUsage($id)
    {
        // Public access allowed without login for reporting (pemakaian)
        $p3k = P3k::findOrFail($id);

        $items = P3kTypeItem::join('p3k_items', 'p3k_type_items.p3k_item_id', '=', 'p3k_items.id')
            ->where('p3k_type_items.p3k_type_id', $p3k->p3k_type_id)
            ->select('p3k_items.id', 'p3k_items.name', 'p3k_items.type')
            ->orderBy('p3k_items.name')
            ->get();

        $departments = Department::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('P3k/UsageReport', [
            'box'         => $p3k,
            'medicines'   => $items,
            'departments' => $departments,
            'mode'        => 'out',
        ]);
    }

    public function createRestock($id)
    {
        abort_unless(Auth::user()->can('create-p3k-restock'), 403, 'Akses Ditolak: Hanya Tim K3 yang dapat melakukan penambahan stok.');

        $p3k = P3k::findOrFail($id);

        $items = P3kTypeItem::join('p3k_items', 'p3k_type_items.p3k_item_id', '=', 'p3k_items.id')
            ->where('p3k_type_items.p3k_type_id', $p3k->p3k_type_id)
            ->select('p3k_items.id', 'p3k_items.name', 'p3k_items.type')
            ->orderBy('p3k_items.name')
            ->get();

        $departments = Department::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('P3k/UsageReport', [
            'box'         => $p3k,
            'medicines'   => $items,
            'departments' => $departments,
            'mode'        => 'in',
        ]);
    }

    public function storeUsage(Request $request, $id)
    {
        $rules = [
            'type'          => 'required|in:out,in',
            'items'         => 'required|array|min:1',
            'items.*.id'    => 'required|exists:p3k_items,id',
            'items.*.qty'   => 'required|integer|min:1',
            'notes'         => 'nullable|string',
        ];

        if ($request->type === 'out') {
            // Public form, no permission check
            $rules['reporter_name'] = 'required|string|max:100';
            $rules['department_id'] = 'required|exists:departments,id';
        }

        if ($request->type === 'in') {
            abort_unless(Auth::user()->can('create-p3k-restock'), 403, 'Akses ditolak: Hanya Tim K3 yang dapat melakukan penambahan stok.');
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();
            $p3k = P3k::findOrFail($id);

            if ($request->type === 'in') {
                $reporterName = Auth::user()->name;
                $deptId       = Auth::user()->department_id;
                $userId       = Auth::id();
            } else {
                $reporterName = $request->reporter_name;
                $deptId       = $request->department_id;
                $userId       = Auth::id(); // Will be null for guest
            }

            foreach ($request->items as $item) {
                $inventory = P3kInventory::firstOrNew([
                    'p3k_id'      => $id,
                    'p3k_item_id' => $item['id'],
                ]);

                $currentQty = $inventory->current_qty ?? 0;
                $changeQty  = $item['qty'];

                if ($request->type === 'out') {
                    $newQty = max(0, $currentQty - $changeQty);
                } else {
                    $newQty = $currentQty + $changeQty;
                }

                $inventory->current_qty = $newQty;
                $inventory->saveQuietly();

                \App\Models\P3kUsage::create([
                    'p3k_id'        => $id,
                    'p3k_item_id'   => $item['id'],
                    'type'          => $request->type,
                    'qty'           => $item['qty'],
                    'user_id'       => $userId, // null for guest
                    'reporter_name' => $reporterName,
                    'department_id' => $deptId,
                    'notes'         => $request->notes,
                ]);
            }

            $standards = P3kTypeItem::where('p3k_type_id', $p3k->p3k_type_id)
                ->pluck('standard', 'p3k_item_id')->toArray();

            $currentInventories = P3kInventory::where('p3k_id', $id)
                ->pluck('current_qty', 'p3k_item_id')->toArray();

            $isCritical = false;
            foreach ($standards as $itemId => $minQty) {
                $stok = $currentInventories[$itemId] ?? 0;
                if ($stok < $minQty) {
                    $isCritical = true;
                    break;
                }
            }

            $p3k->updateQuietly(['status' => $isCritical ? 'critical' : 'safe']);

            DB::commit();

            return redirect()->route('p3k.menu', ['id' => $id])
                ->with('success', 'Laporan berhasil disimpan. Terima kasih ' . $reporterName . '!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }
}