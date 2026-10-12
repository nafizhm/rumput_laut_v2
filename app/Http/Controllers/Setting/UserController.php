<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Menu;
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

        $labels = [
            'users' => 'Manajemen Pengguna', 'dashboard' => 'Dashboard', 'menu' => 'Manajemen Menu',
            'permission' => 'Manajemen Hak Akses', 'role' => 'Manajemen Role',
            'company-profile' => 'Profil Perusahaan', 'harvest-finance' => 'Pembayaran Panen',
            'manajemen-petani' => 'Data Petani', 'manajemen-kategori-pengeluaran' => 'Kategori Pengeluaran',
            'manajemen-pabrik' => 'Data Pabrik', 'manajemen-inventori' => 'Data Inventori',
            'quality' => 'Kualitas Barang', 'area' => 'Area Gudang', 'receiving' => 'Penerimaan Barang',
            'production' => 'Proses Harian', 'opening-stock' => 'Stok Awal',
            'manajemen-pinjaman' => 'Pinjaman', 'manajemen-panen' => 'Penerimaan Panen',
            'manajemen-pemasukan-pengeluaran' => 'Pemasukan dan Pengeluaran', 'manajemen-gudang' => 'Manajemen Gudang',
            'manajemen-penjualan' => 'Penjualan', 'cash-flow' => 'Laporan Arus Kas',
            'coa' => 'Daftar Akun', 'journal' => 'Jurnal', 'financial-reports' => 'Laporan Keuangan',
            'invoice' => 'Invoice', 'surat-jalan' => 'Surat Jalan', 'po-konsumen' => 'PO Konsumen',
        ];
        foreach (Menu::where('is_active', true)->whereNotNull('permission')->orderBy('order')->get() as $menu) {
            $group = explode('.', $menu->permission)[0];
            $labels[$group] ??= $menu->name;
        }
        $actions = ['read' => 'read', 'create' => 'create', 'update' => 'update', 'delete' => 'delete'];
        $special = [
            'confirm' => ['Konfirmasi', 'update'], 'price' => ['Tentukan Harga', 'update'],
            'pay' => ['Pembayaran', 'create'], 'post' => ['Posting', 'update'], 'reverse' => ['Pembalikan', 'update'],
        ];
        $rowsByGroup = [];
        foreach ($permissions->groupBy(fn ($permission) => explode('.', $permission->name)[0]) as $group => $items) {
            $label = $labels[$group] ?? \Illuminate\Support\Str::headline($group);
            $cells = [];
            $extra = [];
            foreach ($items as $permission) {
                $action = explode('.', $permission->name, 2)[1] ?? '';
                if (isset($actions[$action])) {
                    $cells[$actions[$action]] = $permission;
                } else {
                    [$name, $column] = $special[$action] ?? [\Illuminate\Support\Str::headline($action), 'update'];
                    $extra[] = ['label' => $label.' - '.$name, 'suffix' => ' - '.$name, 'cells' => [$column => $permission]];
                }
            }
            $rowsByGroup[$group] = $cells ? [['label' => $label, 'cells' => $cells]] : [];
            array_push($rowsByGroup[$group], ...$extra);
        }

        $menus = Menu::where('is_active', true)->orderBy('order')->orderBy('id')->get();
        $menuGroups = $menus->mapWithKeys(function ($menu) {
            $permission = \App\Services\Auth\RoutePermissionService::permission($menu->route);
            return [$menu->id => $permission ? explode('.', $permission)[0] : null];
        });
        $menuLabels = $menus->filter(fn ($menu) => $menuGroups[$menu->id] !== null)
            ->groupBy(fn ($menu) => $menuGroups[$menu->id])
            ->map(fn ($items) => $items->pluck('name')->unique()->join(' / '));
        $usedGroups = [];
        $walk = function ($parentId, int $level, array $parents = []) use (&$walk, &$usedGroups, $menus, $menuGroups, $menuLabels, $rowsByGroup): array {
            $rows = [];
            foreach ($menus->where('parent_id', $parentId) as $menu) {
                $group = $menuGroups[$menu->id];
                $ownRows = [];
                if ($group && isset($rowsByGroup[$group]) && ! isset($usedGroups[$group])) {
                    $usedGroups[$group] = true;
                    foreach ($rowsByGroup[$group] as $row) {
                        $ownRows[] = array_merge($row, [
                            'label' => $menuLabels[$group].($row['suffix'] ?? ''),
                            'level' => $level + (isset($row['suffix']) ? 1 : 0),
                            'is_parent' => $level === 0 && ! isset($row['suffix']),
                            'parents' => $parents,
                        ]);
                    }
                }
                $children = $walk($menu->id, $level + 1, [...$parents, (string) $menu->id]);
                if ($children && ! $ownRows) {
                    $rows[] = ['label' => $menu->name, 'cells' => [], 'level' => $level, 'is_parent' => true,
                        'parent_key' => (string) $menu->id, 'parents' => $parents];
                }
                array_push($rows, ...$ownRows, ...$children);
            }
            return $rows;
        };
        $permissionRows = $walk(null, 0);
        $remaining = array_diff_key($rowsByGroup, $usedGroups);
        if ($remaining) {
            $permissionRows[] = ['label' => 'Akses Lainnya', 'cells' => [], 'level' => 0, 'is_parent' => true, 'parent_key' => 'other', 'parents' => []];
            foreach ($remaining as $rows) {
                foreach ($rows as $row) {
                    $permissionRows[] = array_merge($row, ['level' => isset($row['suffix']) ? 2 : 1, 'is_parent' => false, 'parents' => ['other']]);
                }
            }
        }

        $selected = old('permissions', session('errors') ? [] : $user->getAllPermissions()->pluck('id')->all());
        $enabledParents = collect($permissionRows)->filter(fn ($row) => collect($row['cells'])->contains(fn ($permission) => in_array($permission->id, $selected)))
            ->flatMap(fn ($row) => $row['parents'])->unique()->values()->all();
        $enabledParents = array_map('strval', old('enabled_parents', session()->hasOldInput('menu_hierarchy') ? [] : $enabledParents));

        return view('pages.settings.user.form', compact('user', 'roles', 'permissionRows', 'enabledParents'));
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
            'menu_hierarchy' => ['nullable', 'in:1'],
            'enabled_parents' => ['nullable', 'array'],
            'enabled_parents.*' => ['string', 'regex:/^(?:[1-9][0-9]*|other)$/'],
        ]);
        $roles = Role::with('permissions')->whereIn('id', $data['roles'] ?? [])->get();
        $permissions = Permission::whereIn('id', $data['permissions'] ?? [])->get();
        if (! empty($data['menu_hierarchy'])) {
            $menus = Menu::where('is_active', true)->get()->keyBy('id');
            $enabled = $data['enabled_parents'] ?? [];
            $mappedGroups = [];
            $enabledGroups = [];
            foreach ($menus as $menu) {
                $permission = \App\Services\Auth\RoutePermissionService::permission($menu->route);
                if (! $permission) continue;
                $group = explode('.', $permission)[0];
                $mappedGroups[$group] = true;
                $parentId = $menu->parent_id;
                $accessible = true;
                $seen = [];
                while ($parentId !== null) {
                    if (isset($seen[$parentId]) || ! isset($menus[$parentId]) || ! in_array((string) $parentId, $enabled, true)) {
                        $accessible = false;
                        break;
                    }
                    $seen[$parentId] = true;
                    $parentId = $menus[$parentId]->parent_id;
                }
                if ($accessible) $enabledGroups[$group] = true;
            }
            $permissions = $permissions->filter(function ($permission) use ($mappedGroups, $enabledGroups, $enabled) {
                $group = explode('.', $permission->name)[0];
                return isset($mappedGroups[$group]) ? isset($enabledGroups[$group]) : in_array('other', $enabled, true);
            });
        }
        $customAccess = ! $roles->contains('name', 'superadmin');
        $effective = $customAccess ? $permissions : $roles->flatMap->permissions->merge($permissions)->unique('id');
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
        DB::transaction(function () use ($data, $user, $roles, $permissions, $customAccess) {
            $attributes = collect($data)->only(['name', 'username', 'email', 'password'])->all();
            if (empty($attributes['password'])) {
                unset($attributes['password']);
            }
            $user->fill($attributes);
            $user->uses_custom_permissions = $customAccess;
            $user->save();
            $user->syncRoles($roles);
            $user->syncPermissions($permissions);
        });
    }
}
