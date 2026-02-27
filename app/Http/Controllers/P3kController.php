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
        $query = P3k::query()->with(['type', 'room.floor.building']);

        if ($request->search) {
            $query->where('code', 'like', "%{$request->search}%")
                  ->orWhereHas('type', function($q) use ($request) {
                      $q->where('name', 'like', "%{$request->search}%");
                  });
        }

        return Inertia::render('MasterData/Assets/P3k/Index', [
            'p3ks' => $query->latest()->paginate(10)->withQueryString(),
            'p3k_types' => P3kType::all(),
            'rooms' => Room::with('floor.building')->get(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:p3ks',
            'p3k_type_id' => 'required|exists:p3k_types,id',
            'room_id' => 'required|exists:rooms,id', 
            'status' => 'required|in:safe,warning,critical',
            'location_data' => 'nullable|array', 
        ]);

        P3k::create($validated);

        return redirect()->back()->with('success', 'Kotak P3K berhasil ditambahkan!');
    }

    public function update(Request $request, P3k $p3k)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:p3ks,code,' . $p3k->id,
            'p3k_type_id' => 'required|exists:p3k_types,id',
            'room_id' => 'required|exists:rooms,id',
            'status' => 'required|in:safe,warning,critical',
            'location_data' => 'nullable|array',
        ]);

        $p3k->update($validated);

        return redirect()->back()->with('success', 'Data P3K berhasil diperbarui!');
    }

    public function destroy(P3k $p3k)
    {
        $p3k->delete();

        return redirect()->back()->with('success', 'Kotak P3K berhasil dihapus!');
    }

    public function menu(Request $request, $id)
    {
        $p3k = P3k::with(['type', 'room.floor.building'])->findOrFail($id);
        
        $inspection = null;
        if ($request->inspection_id) {
            $inspection = Inspection::find($request->inspection_id);
        }

        return Inertia::render('P3k/Menu', [
            'asset' => $p3k,
            'inspection' => $inspection
        ]);
    }

    public function createUsage($id)
    {
        $p3k = P3k::findOrFail($id);

        $items = P3kTypeItem::join('p3k_items', 'p3k_type_items.p3k_item_id', '=', 'p3k_items.id')
            ->where('p3k_type_items.p3k_type_id', $p3k->p3k_type_id)
            ->select('p3k_items.id', 'p3k_items.name', 'p3k_items.type')
            ->orderBy('p3k_items.name')
            ->get();

        $departments = Department::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('P3k/UsageReport', [
            'box' => $p3k,
            'medicines' => $items,
            'departments' => $departments,
            'mode' => 'out'
        ]);
    }

    public function createRestock($id)
    {
        $p3k = P3k::findOrFail($id);

        $items = P3kTypeItem::join('p3k_items', 'p3k_type_items.p3k_item_id', '=', 'p3k_items.id')
            ->where('p3k_type_items.p3k_type_id', $p3k->p3k_type_id)
            ->select('p3k_items.id', 'p3k_items.name', 'p3k_items.type')
            ->orderBy('p3k_items.name')
            ->get();

        $departments = Department::select('id', 'name')->orderBy('name')->get();

        $isK3 = Auth::check() && optional(Auth::user()->department)->name === 'K3';
        if (!$isK3) {
            abort(403, 'Akses Ditolak: Hanya Tim K3 yang dapat melakukan penambahan stok.');
        }

        return Inertia::render('P3k/UsageReport', [
            'box' => $p3k,
            'medicines' => $items,
            'departments' => $departments,
            'mode' => 'in'
        ]);
    }

    public function storeUsage(Request $request, $id)
    {
        $rules = [
            'type' => 'required|in:out,in', 
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:p3k_items,id',
            'items.*.qty' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ];

        if ($request->type === 'out') {
            $rules['reporter_name'] = 'required|string|max:100';
            $rules['department_id'] = 'required|exists:departments,id';
        }

        $request->validate($rules);

        if ($request->type === 'in') {
            $isK3 = Auth::check() && optional(Auth::user()->department)->name === 'K3';
            if (!$isK3) {
                return back()->with('error', 'Akses ditolak: Hanya tim K3 yang dapat melakukan penambahan stok.');
            }
        }

        try {
            DB::beginTransaction();
            $p3k = P3k::findOrFail($id);

            if ($request->type === 'in') {
                // Ambil otomatis dari Auth
                $reporterName = Auth::user()->name;
                $deptId = Auth::user()->department_id;
                $userId = Auth::id();
            } else {
                $reporterName = $request->reporter_name;
                $deptId = $request->department_id;
                $userId = Auth::id();
            }

            foreach ($request->items as $item) {
                $inventory = P3kInventory::firstOrNew([
                    'p3k_id' => $id,
                    'p3k_item_id' => $item['id']
                ]);

                $currentQty = $inventory->current_qty ?? 0;
                $changeQty = $item['qty'];

                if ($request->type === 'out') {
                    $newQty = max(0, $currentQty - $changeQty); 
                } else {
                    $newQty = $currentQty + $changeQty; 
                }

                $inventory->current_qty = $newQty;
                $inventory->save();

                DB::table('p3k_usages')->insert([
                    'p3k_id' => $id,
                    'p3k_item_id' => $item['id'],
                    'user_id' => $userId,
                    'reporter_name' => $reporterName, 
                    'department_id' => $deptId,       
                    'type' => $request->type,
                    'qty' => $item['qty'],
                    'notes' => $request->notes,
                    'created_at' => now(),
                    'updated_at' => now(),
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

            $p3k->update(['status' => $isCritical ? 'critical' : 'safe']);

            DB::commit();

            return redirect()->route('p3k.menu', ['id' => $id])
                ->with('success', 'Laporan berhasil disimpan. Terima kasih ' . $reporterName . '!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage()); 
        }
    }
}