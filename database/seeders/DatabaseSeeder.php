<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. BUAT DEPARTEMEN
        // Kita butuh K3 (wajib) dan beberapa departemen lain untuk simulasi PIC
        $depts = ['K3', 'IT', 'HR', 'Finance', 'General Affair', 'Operations'];
        
        foreach ($depts as $deptName) {
            Department::firstOrCreate(['name' => $deptName]);
        }

        // 2. BUAT POSISI
        // Posisi standar dalam organisasi
        $positions = ['Manager', 'Supervisor', 'Staff', 'Technician', 'Head of Division'];

        foreach ($positions as $posName) {
            Position::firstOrCreate(['name' => $posName]);
        }

        // --- SIAPKAN DATA REFERENSI UNTUK USER ---
        $deptK3 = Department::where('name', 'K3')->first();
        $deptOthers = Department::where('name', '!=', 'K3')->get(); // Departemen selain K3
        
        $posTechnician = Position::where('name', 'Technician')->first();
        $posStaff = Position::where('name', 'Staff')->first();
        $allPositions = Position::all();
        
        $password = Hash::make('password123'); // Password default: password

        // 3. BUAT 3 USER TIM K3 (Khusus Teknisi K3)
        $k3Users = [
            ['name' => 'Budi K3', 'username' => 'budi_k3'],
            ['name' => 'Siti K3', 'username' => 'siti_k3'],
            ['name' => 'Anto K3', 'username' => 'anto_k3'],
        ];

        foreach ($k3Users as $u) {
            User::create([
                'name'          => $u['name'],
                'username'      => $u['username'],
                'password'      => $password,
                'department_id' => $deptK3->id,     // Masuk Dept K3
                'position_id'   => $posTechnician->id, // Jabatan Teknisi
                'is_active'     => true,
            ]);
        }

        // 4. BUAT 8 USER PIC (User Umum/Admin Ruangan)
        // Kita sebar mereka ke departemen selain K3
        $picUsers = [
            ['name' => 'Rina IT', 'username' => 'rina_it'],
            ['name' => 'Doni HR', 'username' => 'doni_hr'],
            ['name' => 'Eka Finance', 'username' => 'eka_fin'],
            ['name' => 'Fajar Ops', 'username' => 'fajar_ops'],
            ['name' => 'Gita GA', 'username' => 'gita_ga'],
            ['name' => 'Hadi IT', 'username' => 'hadi_it'],
            ['name' => 'Indah HR', 'username' => 'indah_hr'],
            ['name' => 'Jefri Ops', 'username' => 'jefri_ops'],
        ];

        foreach ($picUsers as $index => $u) {
            // Ambil random departemen (selain K3) dan posisi
            $randomDept = $deptOthers->random();
            $randomPos  = $allPositions->random();

            User::create([
                'name'          => $u['name'],
                'username'      => $u['username'],
                'password'      => $password,
                'department_id' => $randomDept->id,
                'position_id'   => $randomPos->id,
                'is_active'     => true,
            ]);
        }
    }
}