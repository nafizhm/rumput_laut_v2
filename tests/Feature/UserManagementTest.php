<?php

use App\Models\User;
use Database\Seeders\UserManagementMenuSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    DB::connection()->getPdo()->sqliteCreateFunction('SUBSTRING_INDEX', fn ($value, $delimiter, $count) => explode($delimiter, $value)[0], 3);
    foreach (['0001_01_01_000000_create_users_table.php', '2026_07_14_000001_add_username_to_users_table.php', '2026_01_01_130309_create_permission_tables.php', '2026_01_01_130425_create_menus_table.php'] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->withoutVite();
    $this->admin = User::create(['name' => 'Admin', 'username' => 'admin', 'email' => 'admin@test.test', 'password' => 'password']);
    $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
    $this->seed(UserManagementMenuSeeder::class);
    (require database_path('migrations/2026_10_12_000007_connect_user_menu_permissions.php'))->up();
    $this->admin->assignRole($role);
    $this->payload = ['name' => 'Staff', 'username' => 'staff', 'email' => 'staff@test.test', 'password' => 'password123', 'password_confirmation' => 'password123'];
});

it('protects user management routes', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
    $user = User::create(['name' => 'Viewer', 'username' => 'viewer', 'email' => 'viewer@test.test', 'password' => 'password']);
    $this->actingAs($user)->get(route('users.index'))->assertForbidden();
    $this->postJson(route('users.store'), $this->payload)->assertForbidden();
    $this->putJson(route('users.update', $this->admin), $this->payload)->assertForbidden();
});

it('creates accounts with hashed passwords and selected access then edits without changing password', function () {
    $permission = Permission::findByName('users.read');
    $role = Role::create(['name' => 'viewer', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);
    $this->actingAs($this->admin)->get(route('users.create'))->assertOk()->assertSee('Hak Akses Menu');
    $this->post(route('users.store'), $this->payload + ['roles' => [$role->id], 'permissions' => [$permission->id]])->assertRedirect(route('users.index'));
    $user = User::where('username', 'staff')->firstOrFail();
    expect(Hash::check('password123', $user->password))->toBeTrue()
        ->and($user->hasRole('viewer'))->toBeTrue()->and($user->hasPermissionTo('users.read'))->toBeTrue();
    $hash = $user->password;
    $this->get(route('users.index'))->assertOk()->assertSee('staff');
    $this->get(route('users.edit', $user))->assertOk();
    $this->put(route('users.update', $user), array_replace($this->payload, ['name' => 'Updated', 'password' => '', 'password_confirmation' => '']))->assertRedirect(route('users.index'));
    expect($user->refresh()->password)->toBe($hash)->and($user->name)->toBe('Updated')->and($user->getAllPermissions())->toHaveCount(0);
});

it('rejects duplicate usernames and invalid passwords', function () {
    $this->actingAs($this->admin)->postJson(route('users.store'), array_replace($this->payload, ['username' => 'admin', 'password_confirmation' => 'wrong']))
        ->assertUnprocessable()->assertJsonValidationErrors(['username', 'password']);
});

it('prevents granting permissions and roles beyond the operators access', function () {
    $permission = Permission::create(['name' => 'finance.secret', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
    $this->actingAs($this->admin)->postJson(route('users.store'), $this->payload + ['permissions' => [$permission->id]])->assertForbidden();
    $this->postJson(route('users.store'), $this->payload + ['roles' => [$role->id]])->assertForbidden();
    expect(User::where('username', 'staff')->exists())->toBeFalse();
});

it('preserves own management access and the last superadmin', function () {
    $this->actingAs($this->admin)->putJson(route('users.update', $this->admin), array_replace($this->payload, ['username' => 'admin', 'email' => 'admin@test.test']))->assertUnprocessable();
    $role = Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
    $role->givePermissionTo(['users.read', 'users.create', 'users.update']);
    $this->admin->syncRoles([$role]);
    $this->putJson(route('users.update', $this->admin), array_replace($this->payload, ['username' => 'admin', 'email' => 'admin@test.test', 'permissions' => [Permission::findByName('users.update')->id]]))->assertUnprocessable();
});

it('uses the selected permissions instead of the warehouse roles inherited master and sales access', function () {
    foreach (['manajemen-gudang.read', 'manajemen-gudang.update', 'manajemen-petani.read', 'manajemen-penjualan.read',
        'invoice.read', 'surat-jalan.read', 'po-konsumen.read'] as $name) {
        $permission = Permission::findOrCreate($name, 'web');
        $this->admin->givePermissionTo($permission);
    }
    $warehouseRole = Role::create(['name' => 'gudang', 'guard_name' => 'web']);
    $warehouseRole->givePermissionTo(['manajemen-gudang.read', 'manajemen-petani.read', 'manajemen-penjualan.read']);
    $this->actingAs($this->admin)->post(route('users.store'), $this->payload + [
        'roles' => [$warehouseRole->id], 'permissions' => [Permission::findByName('manajemen-gudang.read')->id],
    ])->assertRedirect(route('users.index'));
    $user = User::where('username', 'staff')->firstOrFail();
    expect($user->uses_custom_permissions)->toBeTrue()->and($user->hasRole('gudang'))->toBeTrue()
        ->and($user->can('manajemen-gudang.read'))->toBeTrue()->and($user->can('manajemen-petani.read'))->toBeFalse()
        ->and($user->hasPermissionTo('manajemen-penjualan.read'))->toBeFalse()
        ->and($user->getAllPermissions()->pluck('name')->all())->toBe(['manajemen-gudang.read']);

    $parents = [];
    foreach (['Manajemen Gudang', 'Master', 'Penjualan'] as $name) {
        $parents[$name] = \App\Models\Menu::create(['name' => $name, 'is_active' => true]);
    }
    foreach ([['Manajemen Gudang', 'manajemen-gudang.index'], ['Master', 'manajemen-petani.index'],
        ['Penjualan', 'po-konsumen.index'], ['Penjualan', 'invoice.index'], ['Penjualan', 'surat-jalan.index']] as [$name, $route]) {
        \App\Models\Menu::create(['name' => $route, 'route' => $route, 'parent_id' => $parents[$name]->id, 'is_active' => true, 'permission' => null]);
    }
    $this->actingAs($user);
    $menus = \App\Services\Sidebar\SidebarService::getSidebarMenu();
    expect($menus->pluck('name')->all())->toBe(['Manajemen Gudang']);
    $this->get(route('access.home'))->assertRedirect(route('manajemen-gudang.index'));
    foreach (['/manajemen-petani', '/farmer-data', '/manajemen-penjualan', '/sales-data', '/po-konsumen', '/invoice', '/surat-jalan', '/dashboard', '/setting/users'] as $url) {
        $this->get($url)->assertForbidden();
    }
    $this->post('/manajemen-gudang/adjustment', [])->assertForbidden();
    $this->post('/invoice', [])->assertForbidden();
    $this->put('/manajemen-petani/1', [])->assertForbidden();
    $this->delete('/po-konsumen/1')->assertForbidden();
});

it('maps every protected application route to a permission', function () {
    foreach (\Illuminate\Support\Facades\Route::getRoutes() as $route) {
        if (! in_array(\App\Http\Middleware\EnsureRoutePermission::class, $route->gatherMiddleware(), true)) continue;
        if (in_array($route->getName(), ['logout', 'access.home', 'access.empty'], true)) continue;
        expect(\App\Services\Auth\RoutePermissionService::permission($route->getName()))->not->toBeNull();
    }
});

it('denies every protected URL and action when no menu access is selected even with an admin role', function () {
    $user = User::create(['name' => 'Restricted', 'username' => 'restricted', 'email' => 'restricted@test.test', 'password' => 'password']);
    $user->assignRole('admin');
    $user->forceFill(['uses_custom_permissions' => true])->save();
    $this->actingAs($user);
    foreach (\Illuminate\Support\Facades\Route::getRoutes() as $route) {
        if (! in_array(\App\Http\Middleware\EnsureRoutePermission::class, $route->gatherMiddleware(), true)) continue;
        if (in_array($route->getName(), ['logout', 'access.home', 'access.empty'], true)) continue;
        $url = '/'.preg_replace('/\{[^}]+\}/', '1', $route->uri());
        $this->call($route->methods()[0], $url)->assertForbidden();
    }
});

it('redirects warehouse login to its permitted page instead of the general dashboard', function () {
    $permission = Permission::findOrCreate('manajemen-gudang.read', 'web');
    $user = User::create(['name' => 'Warehouse', 'username' => 'warehouse', 'email' => 'warehouse@test.test', 'password' => 'password']);
    $user->givePermissionTo($permission);
    $this->post('/login', ['username' => 'warehouse', 'password' => 'password'])->assertRedirect(route('manajemen-gudang.index'));
});

it('removes submenu access when the parent is unchecked even if child permissions are submitted', function () {
    $permission = Permission::findOrCreate('manajemen-gudang.read', 'web');
    $this->admin->givePermissionTo($permission);
    $parent = \App\Models\Menu::create(['name' => 'Manajemen Gudang', 'is_active' => true]);
    \App\Models\Menu::create(['name' => 'Dashboard Gudang', 'route' => 'manajemen-gudang.index', 'parent_id' => $parent->id, 'is_active' => true]);
    $this->actingAs($this->admin)->post(route('users.store'), $this->payload + [
        'menu_hierarchy' => '1', 'enabled_parents' => [(string) $parent->id], 'permissions' => [$permission->id],
    ])->assertRedirect(route('users.index'));
    $user = User::where('username', 'staff')->firstOrFail();
    expect($user->can('manajemen-gudang.read'))->toBeTrue();
    $form = $this->get(route('users.edit', $user))->assertOk()->assertSee('name="enabled_parents[]"', false);
    expect($form->viewData('enabledParents'))->toContain((string) $parent->id);
    $this->put(route('users.update', $user), array_replace($this->payload, [
        'password' => '', 'password_confirmation' => '', 'menu_hierarchy' => '1',
        'enabled_parents' => [], 'permissions' => [$permission->id],
    ]))->assertRedirect(route('users.index'));
    expect($user->fresh()->can('manajemen-gudang.read'))->toBeFalse();
    $this->actingAs($user->fresh())->get('/manajemen-gudang')->assertForbidden();
    expect(\App\Services\Sidebar\SidebarService::getSidebarMenu()->pluck('name')->all())->not->toContain('Manajemen Gudang');
});

it('shows each menu action only for its corresponding selected permission', function ($path, $module, $create, $edit, $delete) {
    $source = file_get_contents(resource_path('views/pages/'.$path.'/index.blade.php'));
    preg_match_all('/<x-slot:(?:headerAction|action)>(.*?)<\/x-slot:(?:headerAction|action)>/s', $source, $matches);
    $template = implode("\n", $matches[1]);
    $user = User::create(['name' => 'Action Tester', 'username' => 'action-tester', 'email' => 'action-tester@test.test', 'password' => 'password']);
    $this->actingAs($user);
    foreach (['read', 'create', 'update', 'delete'] as $action) {
        $permission = Permission::findOrCreate($module.'.'.$action, 'web');
        $user->syncPermissions([$permission]);
        $html = \Illuminate\Support\Facades\Blade::render($template);
        foreach (['create' => $create, 'update' => $edit, 'delete' => $delete] as $required => $marker) {
            expect(str_contains($html, $marker))->toBe($action === $required);
        }
    }
})->with([
    ['master/farmer', 'manajemen-petani', 'create-farmer', 'edit-farmer', 'delete-farmer'],
    ['master/factory', 'manajemen-pabrik', 'create-entity', 'edit-entity', 'delete-entity'],
    ['master/inventory', 'manajemen-inventori', 'create-entity', 'edit-entity', 'delete-entity'],
    ['master/expense-category', 'manajemen-kategori-pengeluaran', 'create-entity', 'edit-entity', 'delete-entity'],
    ['settings/menu', 'menu', 'create-menu', 'edit-menu', 'delete-menu'],
    ['settings/permission', 'permission', 'create-permission', 'edit-permission', 'delete-permission'],
    ['settings/role', 'role', 'create-role', 'edit-role', 'delete-role'],
    ['transaction/loan', 'manajemen-pinjaman', 'create-loan', 'edit-loan', 'delete-loan'],
    ['transaction/transaction', 'manajemen-pemasukan-pengeluaran', 'create-entity', 'edit-entity', 'delete-entity'],
    ['sales/customer-po', 'po-konsumen', 'create-entity', 'edit-entity', 'delete-entity'],
    ['sales/delivery-note', 'surat-jalan', '/surat-jalan/create', 'edit-entity', 'delete-entity'],
    ['sales/invoice', 'invoice', '/invoice/create', '/edit`', 'delete-entity'],
]);

it('applies user access immediately after removing a checkbox and does not change the role for other users', function () {
    $role = Role::create(['name' => 'shared', 'guard_name' => 'web']);
    $role->givePermissionTo('users.read');
    $user = User::create(['name' => 'Staff', 'username' => 'staff', 'email' => 'staff@test.test', 'password' => 'password']);
    $other = User::create(['name' => 'Other', 'username' => 'other', 'email' => 'other@test.test', 'password' => 'password']);
    $user->assignRole($role);
    $other->assignRole($role);
    $this->actingAs($this->admin)->put(route('users.update', $user), array_replace($this->payload, [
        'password' => '', 'password_confirmation' => '', 'roles' => [$role->id], 'permissions' => [],
    ]))->assertRedirect(route('users.index'));
    expect($user->fresh()->can('users.read'))->toBeFalse()->and($other->fresh()->can('users.read'))->toBeTrue();
    $this->actingAs($user->fresh())->get(route('users.index'))->assertForbidden();
    $this->get(route('access.home'))->assertRedirect(route('access.empty'));
    $this->get(route('access.empty'))->assertOk();
});
