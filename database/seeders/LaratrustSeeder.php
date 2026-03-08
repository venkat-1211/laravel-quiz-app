<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laratrust\Models\Role;
use Laratrust\Models\Permission;

class LaratrustSeeder extends Seeder
{
    public function run()
    {
        // Create permissions
        $permissions = [
            // Quiz permissions
            'create-quiz',
            'edit-quiz',
            'delete-quiz',
            'publish-quiz',
            'view-quiz',
            
            // Question permissions
            'create-question',
            'edit-question',
            'delete-question',
            'bulk-upload-questions',
            
            // Category permissions
            'create-category',
            'edit-category',
            'delete-category',
            
            // User permissions
            'view-users',
            'manage-users',
            
            // Report permissions
            'view-reports',
            'export-reports',
            
            // Admin permissions
            'access-admin',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Assign permissions to admin
        $adminRole->permissions()->sync(Permission::all());

        // Assign basic permissions to user
        $userPermissions = Permission::whereIn('name', [
            'view-quiz',
            'attempt-quiz',
            'view-profile',
        ])->get();
        
        $userRole->permissions()->sync($userPermissions);
    }
}