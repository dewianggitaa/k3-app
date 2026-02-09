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
        $tipeA = P3kType::create(['name' => 'Kotak A']); // Utk 25 pekerja/kurang
        $tipeB = P3kType::create(['name' => 'Kotak B']); // Utk 50 pekerja/kurang
        $tipeC = P3kType::create(['name' => 'Kotak C']); // Utk 100 pekerja/kurang


        $items = [
            ['name' => 'Kasa steril terbungkus',        'type' => 'consumable', 'qty_a' => 20, 'qty_b' => 40, 'qty_c' => 40],
            ['name' => 'Perban (Lebar 5 cm)',           'type' => 'consumable', 'qty_a' => 2,  'qty_b' => 4,  'qty_c' => 6],
            ['name' => 'Perban (Lebar 10 cm)',          'type' => 'consumable', 'qty_a' => 2,  'qty_b' => 4,  'qty_c' => 6],
            ['name' => 'Plester (Lebar 1,25 cm)',       'type' => 'consumable', 'qty_a' => 2,  'qty_b' => 4,  'qty_c' => 6],
            ['name' => 'Plester Cepat',                 'type' => 'consumable', 'qty_a' => 10, 'qty_b' => 15, 'qty_c' => 20],
            
            ['name' => 'Kapas (25 gram)',               'type' => 'consumable', 'qty_a' => 1,  'qty_b' => 2,  'qty_c' => 3],
            ['name' => 'Kain segitiga',                 'type' => 'consumable',       'qty_a' => 2,  'qty_b' => 4,  'qty_c' => 6],
            ['name' => 'Gunting',                       'type' => 'multi_use',       'qty_a' => 1,  'qty_b' => 1,  'qty_c' => 1],
            ['name' => 'Peniti',                        'type' => 'multi_use',       'qty_a' => 12, 'qty_b' => 12, 'qty_c' => 12],
            ['name' => 'Sepasang sarung tangan sekali pakai', 'type' => 'consumable', 'qty_a' => 2, 'qty_b' => 3, 'qty_c' => 4],
            
            ['name' => 'Masker',                        'type' => 'consumable', 'qty_a' => 1,  'qty_b' => 1,  'qty_c' => 1],
            ['name' => 'Pinset',                        'type' => 'multi_use',       'qty_a' => 1,  'qty_b' => 1,  'qty_c' => 1],
            ['name' => 'Lampu Senter',                  'type' => 'multi_use',       'qty_a' => 1,  'qty_b' => 1,  'qty_c' => 1],
            ['name' => 'Gelas untuk cuci mata',         'type' => 'multi_use',       'qty_a' => 1,  'qty_b' => 2,  'qty_c' => 3],
            ['name' => 'Kantong plastik bersih',        'type' => 'consumable', 'qty_a' => 1,  'qty_b' => 1,  'qty_c' => 1],
            
            ['name' => 'Aquades (100 mL larutan Saline)', 'type' => 'multi_use', 'qty_a' => 1, 'qty_b' => 1, 'qty_c' => 1],
            ['name' => 'Povidon Iodin (60 ml)',         'type' => 'multi_use', 'qty_a' => 1,  'qty_b' => 1,  'qty_c' => 1],
            ['name' => 'Alkohol 70%',                   'type' => 'multi_use', 'qty_a' => 1,  'qty_b' => 1,  'qty_c' => 1],
            ['name' => 'Buku panduan P3K di tempat kerja', 'type' => 'multi_use',    'qty_a' => 1,  'qty_b' => 1,  'qty_c' => 1],
            ['name' => 'Buku Catatan',                  'type' => 'multi_use',       'qty_a' => 1,  'qty_b' => 1,  'qty_c' => 1],
        ];

        foreach ($items as $itemData) {
            // Create Item Master
            $item = P3kItem::create([
                'name' => $itemData['name'],
                'type' => $itemData['type']
            ]);

            // Hubungkan ke Tipe A
            P3kTypeItem::create([
                'p3k_type_id' => $tipeA->id,
                'p3k_item_id' => $item->id,
                'quantity'    => $itemData['qty_a']
            ]);

            // Hubungkan ke Tipe B
            P3kTypeItem::create([
                'p3k_type_id' => $tipeB->id,
                'p3k_item_id' => $item->id,
                'quantity'    => $itemData['qty_b']
            ]);

            // Hubungkan ke Tipe C
            P3kTypeItem::create([
                'p3k_type_id' => $tipeC->id,
                'p3k_item_id' => $item->id,
                'quantity'    => $itemData['qty_c']
            ]);
        }

        $p3k = P3k::create([
            'code'          => 'P3K-L1-001',
            'p3k_type_id'   => $tipeA->id,
            'room_id'       => 1, 
            'status'        => 'safe'
        ]);
        
        $standarItems = P3kTypeItem::where('p3k_type_id', $tipeA->id)->get();

        foreach ($standarItems as $standard) {
            P3kInventory::create([
                'p3k_id'      => $p3k->id,
                'p3k_item_id' => $standard->p3k_item_id,
                'current_qty' => $standard->quantity
            ]);
        }
    }
}