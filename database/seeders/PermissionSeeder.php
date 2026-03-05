<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Manajemen Pengguna
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            'toggle-user-status',
            'manage-roles',

            // Data Master
            'manage-buildings',
            'manage-floors',
            'manage-rooms',
            'manage-assets',
            'manage-asset-mapping',
            'manage-checklist-parameters',

            // Penjadwalan
            'view-schedules',
            'create-schedules',
            'delete-schedules',

            // Inspeksi
            'manage-inspections',
            'view-assigned-inspections',
            'execute-inspections',
            'delete-inspections',
            'edit-inspections',

            // P3K Operasional
            'create-p3k-usage',
            'create-p3k-restock',

            // Laporan
            'view-dashboard',
            'view-reports',
            'export-reports',
            'view-pic-reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
