<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ChecklistParameter; // Pastikan model ini ada

class ChecklistParameterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Bersihkan data lama agar tidak duplikat saat seeding ulang
        // DB::table('checklist_parameters')->truncate(); 

        // --- 1. PARAMETER UNTUK APAR (Fire Extinguisher) ---
        // Sumber: Dokumen IK Penggunaan & Pemeliharaan APAR (6.2)
        $aparParams = [
            [
                'asset_type'    => 'apar',
                'label'         => 'Posisi Jarum Manometer',
                'input_type'    => 'radio',
                'options'       => json_encode(['Di Area Hijau (Normal)', 'Di Area Merah (Over/Under)']),
                'standard_value'=> 'Jarum harus berada tepat di area hijau (tekanan normal 13-15 bar).',
                'order_index'   => 1,
            ],
            [
                'asset_type'    => 'apar',
                'label'         => 'Segel Pengaman (Safety Pin)',
                'input_type'    => 'radio',
                'options'       => json_encode(['Utuh & Terpasang', 'Putus / Hilang']),
                'standard_value'=> 'Segel/Pin pengaman harus terpasang kuat dan tidak putus.',
                'order_index'   => 2,
            ],
            [
                'asset_type'    => 'apar',
                'label'         => 'Kondisi Selang (Hose)',
                'input_type'    => 'radio',
                'options'       => json_encode(['Mulus & Lentur', 'Retak / Kaku / Bocor']),
                'standard_value'=> 'Selang tidak boleh retak, pecah, atau tersumbat.',
                'order_index'   => 3,
            ],
            [
                'asset_type'    => 'apar',
                'label'         => 'Fisik Tabung',
                'input_type'    => 'radio',
                'options'       => json_encode(['Mulus', 'Berkarat / Penyok']),
                'standard_value'=> 'Tabung bersih, tidak ada korosi (karat) dan tidak penyok.',
                'order_index'   => 4,
            ],
            [
                'asset_type'    => 'apar',
                'label'         => 'Label & Instruksi',
                'input_type'    => 'radio',
                'options'       => json_encode(['Terbaca Jelas', 'Pudar / Sobek']),
                'standard_value'=> 'Stiker instruksi dan masa berlaku harus terbaca dengan jelas.',
                'order_index'   => 5,
            ],
        ];

        // --- 2. PARAMETER UNTUK HYDRANT (Indoor & Outdoor) ---
        // Sumber: Dokumen IK Pemeliharaan Hydrant (6.3)
        $hydrantParams = [
            [
                'asset_type'    => 'hydrant',
                'label'         => 'Kebersihan & Akses Box',
                'input_type'    => 'radio',
                'options'       => json_encode(['Bersih & Terjangkau', 'Kotor / Terhalang']),
                'standard_value'=> 'Box hydrant tidak terhalang benda lain dan dalam kondisi bersih.',
                'order_index'   => 1,
            ],
            [
                'asset_type'    => 'hydrant',
                'label'         => 'Kelengkapan Nozzle',
                'input_type'    => 'radio',
                'options'       => json_encode(['Ada & Baik', 'Hilang / Rusak']),
                'standard_value'=> 'Nozzle penyemprot harus tersedia di dalam box dan tidak macet.',
                'order_index'   => 2,
            ],
            [
                'asset_type'    => 'hydrant',
                'label'         => 'Kondisi Selang (Hose)',
                'input_type'    => 'radio',
                'options'       => json_encode(['Kering & Rapi', 'Basah / Berantakan / Bocor']),
                'standard_value'=> 'Selang tergulung rapi (jika indoor) dan tidak ada kebocoran.',
                'order_index'   => 3,
            ],
            [
                'asset_type'    => 'hydrant',
                'label'         => 'Kunci Hydrant',
                'input_type'    => 'radio',
                'options'       => json_encode(['Tersedia', 'Hilang']),
                'standard_value'=> 'Kunci pembuka valve/box harus tersedia di tempatnya.',
                'order_index'   => 4,
            ],
            [
                'asset_type'    => 'hydrant',
                'label'         => 'Tes Aliran Air (Valve)',
                'input_type'    => 'radio',
                'options'       => json_encode(['Bertekanan Kuat', 'Lemah / Tidak Keluar']),
                'standard_value'=> 'Saat valve dibuka sedikit, air harus keluar dengan tekanan yang cukup.',
                'order_index'   => 5,
            ],
        ];

        // --- 3. Insert ke Database ---
        
        $allParams = array_merge($aparParams, $hydrantParams);

        foreach ($allParams as $param) {
            ChecklistParameter::create($param);
        }
    }
}