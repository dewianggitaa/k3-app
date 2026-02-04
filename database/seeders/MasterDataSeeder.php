<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Matikan Foreign Key Check biar bisa truncate (bersihin data lama)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Room::truncate();
        Floor::truncate();
        Building::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Definisi Data Gedung
        $gedungs = [
            ['name' => 'Office'],
            ['name' => 'Pharma'],
            ['name' => 'Utility'],
            ['name' => 'Herbal'],
            ['name' => 'Lingkungan'],
        ];

        foreach ($gedungs as $dataGedung) {
            $building = Building::create([
                'name' => $dataGedung['name']
            ]);

            $this->command->info("Membuat Gedung: {$building->name}");

            for ($i = 1; $i <= 4; $i++) {
                $floor = Floor::create([
                    'building_id' => $building->id,
                    'name' => 'Lantai ' . $i,
                ]);

                $ruangans = ['A', 'B', 'C'];
                foreach ($ruangans as $ruang) {
                    Room::create([
                        'floor_id' => $floor->id,
                        'name' => "Ruang {$ruang}", 
                    ]);
                }
            }
        }
    }
}