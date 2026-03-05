<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::user()->can('view-users'), 403, 'Anda tidak memiliki izin melihat daftar pengguna.');

        $query = User::with(['department', 'position', 'roles'])->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhereHas('department', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $users = $query->paginate(20)->through(function ($user) {
            return [
                'id'            => $user->id,
                'name'          => $user->name,
                'username'      => $user->username,
                'department'    => $user->department?->name,
                'department_id' => $user->department_id,
                'position'      => $user->position?->name,
                'position_id'   => $user->position_id,
                'is_active'     => $user->is_active,
                'roles'         => $user->roles->pluck('name'),
                'created_at'    => $user->created_at->format('d M Y'),
            ];
        })->withQueryString();

        $roles               = Role::orderBy('name')->get(['id', 'name']);
        $departments         = Department::orderBy('name')->get(['id', 'name']);
        $positions           = Position::orderBy('name')->get(['id', 'name']);
        $rolesWithPermissions = Role::with('permissions')->orderBy('name')->get();

        return Inertia::render('UserRoleManagement/Index', [
            'users'               => $users,
            'roles'               => $roles,
            'departments'         => $departments,
            'positions'           => $positions,
            'rolesWithPermissions' => $rolesWithPermissions,
            'filters'             => $request->only('search', 'tab'),
            'can'                 => [
                'create_users'        => Auth::user()->can('create-users'),
                'edit_users'          => Auth::user()->can('edit-users'),
                'delete_users'        => Auth::user()->can('delete-users'),
                'toggle_user_status'  => Auth::user()->can('toggle-user-status'),
                'manage_roles'        => Auth::user()->can('manage-roles'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->can('create-users'), 403, 'Anda tidak memiliki izin menambah pengguna.');

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'username'      => 'required|string|max:255|unique:users',
            'password'      => 'required|string|min:6',
            'department_id' => 'nullable|exists:departments,id',
            'position_id'   => 'nullable|exists:positions,id',
            'is_active'     => 'boolean',
            'role'          => 'nullable|string|exists:roles,name',
        ]);

        $user = User::create([
            'name'          => $validated['name'],
            'username'      => $validated['username'],
            'password'      => Hash::make($validated['password']),
            'department_id' => $validated['department_id'] ?? null,
            'position_id'   => $validated['position_id'] ?? null,
            'is_active'     => $validated['is_active'] ?? true,
        ]);

        if (!empty($validated['role'])) {
            $user->assignRole($validated['role']);
        }

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        abort_unless(Auth::user()->can('edit-users'), 403, 'Anda tidak memiliki izin mengubah data pengguna.');

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'username'      => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password'      => 'nullable|string|min:6',
            'department_id' => 'nullable|exists:departments,id',
            'position_id'   => 'nullable|exists:positions,id',
            'is_active'     => 'boolean',
            'role'          => 'nullable|string|exists:roles,name',
        ]);

        $user->update([
            'name'          => $validated['name'],
            'username'      => $validated['username'],
            'department_id' => $validated['department_id'] ?? null,
            'position_id'   => $validated['position_id'] ?? null,
            'is_active'     => $validated['is_active'] ?? true,
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        return back()->with('success', 'User berhasil diperbarui.');
    }

    public function toggleStatus(User $user)
    {
        abort_unless(Auth::user()->can('toggle-user-status'), 403, 'Anda tidak memiliki izin mengubah status pengguna.');

        $user->update(['is_active' => !$user->is_active]);
        return back()->with('success', 'Status user berhasil diubah.');
    }

    public function destroy(User $user)
    {
        abort_unless(Auth::user()->can('delete-users'), 403, 'Anda tidak memiliki izin menghapus pengguna.');

        $user->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }
}
