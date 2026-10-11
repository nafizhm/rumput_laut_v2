<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\{Permission, Role};
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_runs', function (Blueprint $table) {
            $table->boolean('warehouse_only')->default(false);
        });
        $role = Role::where('name', 'gudang')->where('guard_name', 'web')->first();
        foreach (['production.read', 'production.create', 'production.confirm'] as $name) {
            $permission = Permission::findOrCreate($name, 'web');
            $role?->givePermissionTo($permission);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('production_runs', fn (Blueprint $table) => $table->dropColumn('warehouse_only'));
    }
};
