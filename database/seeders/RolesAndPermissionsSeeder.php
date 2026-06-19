<?php
namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Setup Permissions
        $permissions = [
            'tasks.create', 'tasks.edit', 'tasks.delete',
            'tasks.assign', 'tasks.cancel', 'tasks.view_all',
            'tasks.update_own_status', 'tasks.comment', 'tasks.upload',
            'reports.view', 'users.manage', 'departments.manage',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // 2. Setup Roles & Assign Permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions(Permission::all());

        $headRole = Role::firstOrCreate(['name' => 'head_department']);
        $headRole->syncPermissions(collect($permissions)->reject(fn($p) => in_array($p, ['users.manage', 'departments.manage']))->toArray());

        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staffRole->syncPermissions([
            'tasks.update_own_status', 'tasks.comment', 'tasks.upload',
        ]);

        // 3. Setup Department Default
        $itDept = Department::firstOrCreate(
            ['code' => 'IT-01'],
            ['name' => 'Information Technology']
        );

        // 4. Create Default Users
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@it.local'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $adminUser->assignRole($adminRole);

        $headUser = User::firstOrCreate(
            ['email' => 'head@it.local'],
            [
                'name' => 'Budi (Head IT)',
                'password' => Hash::make('password'), // default password
                'department_id' => $itDept->id,
            ]
        );
        $headUser->assignRole($headRole);

        $staffUser = User::firstOrCreate(
            ['email' => 'staff@it.local'],
            [
                'name' => 'Andi (IT Staff)',
                'password' => Hash::make('password'), // default password
                'department_id' => $itDept->id,
            ]
        );
        $staffUser->assignRole($staffRole);
    }
}
