<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->boolean('uses_custom_permissions')->default(false));
        // Existing explicit selections become authoritative. Role-only accounts keep their defaults.
        DB::table('users')->whereExists(fn ($q) => $q->selectRaw('1')->from('model_has_permissions')
            ->whereColumn('model_id', 'users.id')->where('model_type', \App\Models\User::class))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('model_has_roles as mr')->join('roles as r', 'r.id', '=', 'mr.role_id')
                ->whereColumn('mr.model_id', 'users.id')->where('mr.model_type', \App\Models\User::class)->where('r.name', 'superadmin'))
            ->update(['uses_custom_permissions' => true]);
        (new \Database\Seeders\MenuAccessSeeder)->run();
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('uses_custom_permissions'));
    }
};
