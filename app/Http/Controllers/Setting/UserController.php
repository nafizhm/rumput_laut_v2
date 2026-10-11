<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->orderBy('name')->paginate(20);

        return view('pages.settings.user.index', compact('users'));
    }

    public function create(Request $request)
    {
        return $this->form($request, new User);
    }

    public function edit(Request $request, User $user)
    {
        $this->checkTarget($request, $user);

        return $this->form($request, $user);
    }

    private function form(Request $request, User $user)
    {
        $permissions = $request->user()->hasRole('superadmin')
            ? Permission::where('guard_name', 'web')->orderBy('name')->get()
            : $request->user()->getAllPermissions()->sortBy('name')->values();
        $allowed = $permissions->pluck('id');
        $roles = Role::where('guard_name', 'web')->with('permissions')->orderBy('name')->get()
            ->filter(fn ($role) => ($role->name !== 'superadmin' || $request->user()->hasRole('superadmin'))
                && $role->permissions->pluck('id')->diff($allowed)->isEmpty());

        return view('pages.settings.user.form', compact('user', 'roles', 'permissions'));
    }

    public function store(Request $request)
    {
        $this->save($request, new User);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $this->checkTarget($request, $user);
        $this->save($request, $user);

        return redirect()->route('users.index')->with('success', 'Pengguna dan hak akses berhasil diperbarui.');
    }

    private function checkTarget(Request $request, User $user): void
    {
        abort_if($user->hasRole('superadmin') && ! $request->user()->hasRole('superadmin'), 403);
        if (! $request->user()->hasRole('superadmin')) {
            abort_if($user->getAllPermissions()->pluck('id')->diff($request->user()->getAllPermissions()->pluck('id'))->isNotEmpty(), 403);
        }
    }

    private function save(Request $request, User $user): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'alpha_dash', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => [$user->exists ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')->where('guard_name', 'web')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')->where('guard_name', 'web')],
        ]);
        $roles = Role::with('permissions')->whereIn('id', $data['roles'] ?? [])->get();
        $permissions = Permission::whereIn('id', $data['permissions'] ?? [])->get();
        $effective = $roles->flatMap->permissions->merge($permissions)->unique('id');
        if (! $request->user()->hasRole('superadmin')) {
            abort_if($roles->contains('name', 'superadmin') || $effective->pluck('id')->diff($request->user()->getAllPermissions()->pluck('id'))->isNotEmpty(), 403);
        }
        if ($user->id === $request->user()->id && ! $effective->contains('name', 'users.update')) {
            throw ValidationException::withMessages(['roles' => 'Hak akses mengelola akun sendiri tidak boleh dihapus.']);
        }
        if ($user->hasRole('superadmin') && ! $roles->contains('name', 'superadmin')
            && User::role('superadmin')->whereKeyNot($user->id)->doesntExist()) {
            throw ValidationException::withMessages(['roles' => 'Minimal satu pengguna harus tetap memiliki role superadmin.']);
        }
        DB::transaction(function () use ($data, $user, $roles, $permissions) {
            $attributes = collect($data)->only(['name', 'username', 'email', 'password'])->all();
            if (empty($attributes['password'])) {
                unset($attributes['password']);
            }
            $user->fill($attributes)->save();
            $user->syncRoles($roles);
            $user->syncPermissions($permissions);
        });
    }
}
