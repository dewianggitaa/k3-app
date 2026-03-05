<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function store(Request $request)
    {
        abort_unless(Auth::user()->can('manage-roles'), 403, 'Anda tidak memiliki izin mengelola role.');

        $validated = $request->validate([
            'name'          => 'required|string|max:255|unique:roles,name',
            'permissions'   => 'array',
            'permissions.*' => 'string',
        ]);

        $role = Role::create(['name' => $validated['name']]);

        if (!empty($validated['permissions'])) {
            foreach ($validated['permissions'] as $permName) {
                Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            }
            $role->syncPermissions($validated['permissions']);
        }

        return back()->with('success', 'Role berhasil dibuat.');
    }

    public function update(Request $request, Role $role)
    {
        abort_unless(Auth::user()->can('manage-roles'), 403, 'Anda tidak memiliki izin mengelola role.');

        $validated = $request->validate([
            'name'          => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions'   => 'array',
            'permissions.*' => 'string',
        ]);

        $role->update(['name' => $validated['name']]);

        if (isset($validated['permissions'])) {
            foreach ($validated['permissions'] as $permName) {
                Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            }
            $role->syncPermissions($validated['permissions']);
        }

        return back()->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        abort_unless(Auth::user()->can('manage-roles'), 403, 'Anda tidak memiliki izin menghapus role.');

        $role->delete();
        return back()->with('success', 'Role berhasil dihapus.');
    }
}
