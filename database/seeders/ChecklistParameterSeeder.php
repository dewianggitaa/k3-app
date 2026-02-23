<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChecklistParameter;
use App\Models\AssetType; // Pastikan model ini ada atau sesuaikan relasinya

class ChecklistParameterSeeder extends Seeder
{
    public function run(): void
    {
        // --- 1. PARAMETER UNTUK APAR (Fire Extinguisher) ---
        // Referensi: Dokumen Instruksi Kerja 6.2 Cara Pemeliharaan APAR
        
        $aparParams = [
            [
                'label' => 'Pemeriksaan Manometer (Tekanan)',
                'description' => 'Pastikan jarum manometer berada di area hijau (tekanan normal).',
                'input_type' => 'radio',
                'options' => json_encode(['Sesuai', 'Tidak Sesuai']),
                'standard' => 'Jarum menunjukkan pada posisi hijau (bertekanan). Jika di merah (kurang/lebih), catat sebagai temuan.',
            ],
            [
                'label' => 'Kondisi Segel (Safety Pin)',
                'description' => 'Periksa apakah segel pengaman masih terpasang utuh.',
                'input_type' => 'radio',
                'options' => json_encode(['Utuh', 'Rusak/Putus']),
                'standard' => 'Segel tidak lepas atau terputus.',
            ],
            [
                'label' => 'Kondisi Selang (Hose)',
                'description' => 'Cek fisik selang dari keretakan atau kebocoran.',
                'input_type' => 'radio',
                'options' => json_encode(['Baik', 'Retak/Bocor']),
                'standard' => 'Selang tidak boleh dalam keadaan tertekuk, retak, maupun bocor dan tidak tersumbat.',
            ],
            [
                'label' => 'Kondisi Label / Penandaan',
                'description' => 'Pastikan label instruksi dan tanggal kadaluarsa terbaca jelas.',
                'input_type' => 'radio',
                'options' => json_encode(['Jelas', 'Pudar/Rusak']),
                'standard' => 'Label ada, tulisan tidak pudar/hilang, dan mencantumkan masa kadaluarsa.',
            ],
            [
                'label' => 'Fisik Tabung',
                'description' => 'Periksa korosi atau penyok pada tabung.',
                'input_type' => 'radio',
                'options' => json_encode(['Mulus', 'Berkarat/Penyok']),
                'standard' => 'Tabung dalam kondisi baik, tidak berkarat berat atau penyok.',
            ],
        ];

        // Masukkan ke DB (Sesuaikan logic pengambilan ID tipe aset kamu)
        // Anggap ID 1 adalah tipe 'APAR'
        foreach ($aparParams as $param) {
            ChecklistParameter::create(array_merge($param, ['asset_type_id' => 1])); 
        }


        // --- 2. PARAMETER UNTUK HYDRANT (Indoor & Outdoor) ---
        // Referensi: Dokumen Instruksi Kerja 6.3 Pemeliharaan Hydrant Indoor dan Outdoor
        
        $hydrantParams = [
            [
                'label' => 'Kelengkapan Prosedur',
                'description' => 'Cek ketersediaan instruksi kerja di lokasi.',
                'input_type' => 'radio',
                'options' => json_encode(['Tersedia & Baik', 'Tidak Ada/Rusak']),
                'standard' => 'Prosedur tersedia, tidak rusak, dan merupakan versi terbaru.',
            ],
            [
                'label' => 'Kondisi Selang (Hose)',
                'description' => 'Cek kondisi fisik selang pemadam.',
                'input_type' => 'radio',
                'options' => json_encode(['Baik & Bersih', 'Bocor/Kotor']),
                'standard' => 'Tersedia selang, tidak bocor, dan dalam kondisi bersih.',
            ],
            [
                'label' => 'Kondisi Nozzle',
                'description' => 'Pastikan nozzle terpasang dan berfungsi.',
                'input_type' => 'radio',
                'options' => json_encode(['Lengkap & Lancar', 'Macet/Hilang']),
                'standard' => 'Tersedia nozzle dan tuas tidak macet saat dicoba.',
            ],
            [
                'label' => 'Kunci Hydrant',
                'description' => 'Pastikan kunci box tersedia di tempatnya.',
                'input_type' => 'radio',
                'options' => json_encode(['Ada', 'Hilang']),
                'standard' => 'Tersedia Kunci Hidran pada box atau lokasi yang ditentukan.',
            ],
            [
                'label' => 'Uji Aliran Air (Flow Test)',
                'description' => 'Buka sedikit valve untuk memastikan air mengalir.',
                'input_type' => 'radio',
                'options' => json_encode(['Bertekanan', 'Tidak Keluar Air']),
                'standard' => 'Ada air bertekanan yang mengalir dari outlet hydrant saat dibuka.',
            ],
        ];

        // Anggap ID 2 adalah tipe 'Hydrant'
        foreach ($hydrantParams as $param) {
            ChecklistParameter::create(array_merge($param, ['asset_type_id' => 2]));
        }
    }
}