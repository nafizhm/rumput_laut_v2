<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function grantModuleAccess(\App\Models\User $user, array $modules): void
    {
        foreach ($modules as $module) {
            foreach (['read', 'create', 'update', 'delete'] as $action) {
                $user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate($module.'.'.$action, 'web'));
            }
        }
    }
}
