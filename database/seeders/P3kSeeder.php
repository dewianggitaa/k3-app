<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\P3kType;
use App\Models\P3kItem;
use App\Models\P3kTypeItem;
use App\Models\P3k;
use App\Models\P3kInventory;


class P3kSeeder extends Seeder
{
public function run(): void
    {
        // 1. Buat Tipe
        $tipeA = \App\Models\P3kType::create(['name' => 'Tipe A']);

        // 2. Buat Item Standar (Contoh beberapa dari 20 item)
        $items = [
            ['name' => 'Kasa Steril 16x16cm', 'type' => 'consumable', 'qty_a' => 20],
            ['name' => 'Perban 5cm', 'type' => 'consumable', 'qty_a' => 2],
            ['name' => 'Plester 1.25cm', 'type' => 'consumable', 'qty_a' => 2],
            ['name' => 'Cairan Pembersih (Betadine)', 'type' => 'multi_use', 'qty_a' => 1],
            ['name' => 'Gunting', 'type' => 'multi_use', 'qty_a' => 1],
        ];

        foreach ($items as $itemData) {
            $item = \App\Models\P3kItem::create([
                'name' => $itemData['name'],
                'type' => $itemData['type']
            ]);

            // Hubungkan ke Tipe A dengan jumlah standar
            \App\Models\P3kTypeItem::create([
                'p3k_type_id' => $tipeA->id,
                'p3k_item_id' => $item->id,
                'quantity' => $itemData['qty_a']
            ]);
        }

        // 3. Buat Dummy Kotak P3K di Lantai 1 (Asumsi ID Floor 1 sudah ada)
        $p3k = \App\Models\P3k::create([
            'code' => 'P3K-L1-001',
            'p3k_type_id' => $tipeA->id,
            'floor_id' => 1, 
            'location_data' => json_encode(['x' => 50, 'y' => 50]), // Taruh di tengah dulu
            'status' => 'safe'
        ]);

        // 4. Isi Inventory Awal (Otomatis sesuai standar Tipe A)
        foreach ($tipeA->items as $standard) {
            \App\Models\P3kInventory::create([
                'p3k_id' => $p3k->id,
                'p3k_item_id' => $standard->id,
                'current_qty' => $standard->pivot->quantity
            ]);
        }
    }
}
