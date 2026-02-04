<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Buat Data Master (Department & Position)
        // Pakai firstOrCreate biar kalau di-run 2x tidak duplikat
        $dept = Department::firstOrCreate(
            ['name' => 'IT Department']
        );

        $pos = Position::firstOrCreate(
            ['name' => 'Head of IT']
        );

        // 2. Buat Role Spatie
        // Reset cache permission dulu (Wajib buat Spatie)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        $role = Role::firstOrCreate(['name' => 'Super Admin']);

        // 3. Buat User Admin
        $user = User::firstOrCreate(
            ['username' => 'admin'], // Cek berdasarkan username
            [
                'name'          => 'Administrator Sistem',
                'password'      => Hash::make('password'), // Password: password
                'department_id' => $dept->id,
                'position_id'   => $pos->id,
                'is_active'     => true,
            ]
        );

        // 4. Assign Role ke User
        $user->assignRole($role);

        $this->call([
            MasterDataSeeder::class, // <--- Tambahin ini
        ]);
    }
}