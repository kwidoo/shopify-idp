<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the admin role if it doesn't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Define permissions for the admin role
        $permissions = [
            'impersonate users',
            'manage users',
            'manage roles',
            'view logs',
        ];

        // Create permissions and assign them to admin role
        foreach ($permissions as $permission) {
            $permissionModel = Permission::firstOrCreate(['name' => $permission]);
            $adminRole->givePermissionTo($permissionModel);
        }

        $this->command->info('Admin role and permissions created successfully.');
    }
}
