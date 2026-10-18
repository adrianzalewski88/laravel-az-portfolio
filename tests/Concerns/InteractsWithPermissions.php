<?php

namespace Tests\Concerns;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

trait InteractsWithPermissions
{
    protected function userWithPermissions(string ...$permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();

        foreach ($permissions as $permissionName) {
            $permission = Permission::findOrCreate($permissionName, 'web');

            $user->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }
}
