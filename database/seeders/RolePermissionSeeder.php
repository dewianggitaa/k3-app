<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $p1 = Permission::firstOrCreate(['name' => 'manage_inspections']); // Mengelola Data Inspeksi
        $p2 = Permission::firstOrCreate(['name' => 'manage_schedules']);   // Mengelola Jadwal & Aset
        $p3 = Permission::firstOrCreate(['name' => 'execute_inspection']); // Mengerjakan Checklist (Lapangan)

        
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        $roleAdmin->givePermissionTo([$p1, $p2]);

        $roleExecutor = Role::firstOrCreate(['name' => 'executor']);
        $roleExecutor->givePermissionTo([$p3]);

        $user = User::find(1);
        
        if ($user) {
            $user->assignRole('admin');
            $this->command->info("User ID 1 ({$user->name}) telah dilantik menjadi Admin!");
        } else {
            $this->command->warn("User ID 1 tidak ditemukan. Pastikan User sudah dibuat sebelum menjalankan seeder ini.");
        }
    }
}