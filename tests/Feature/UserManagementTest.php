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
    $this->actingAs($this->admin)->get(route('users.create'))->assertOk()->assertSee('Hak Akses Tambahan');
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
