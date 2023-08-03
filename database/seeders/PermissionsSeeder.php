<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $permissions = [
            # Role
            'role-view',
            'role-create',
            'role-edit',
            'role-delete',
            # User
            'user-view',
            'user-create',
            'user-edit',
            'user-delete',


        ];

        $role = Role::firstOrCreate(["name" => "Super Admin", "guard_name" => "sanctum" ]);
        $role->syncPermissions([]);
        foreach ($permissions as $key => $value) {
            if(!Permission::where("name",$value)->exists())
            {
                Permission::create(["name" => $value ,"guard_name" => "sanctum"]);
            }
            $role->givePermissionTo($value);
        }

        $admin = User::firstOrCreate([
            'name' => 'Super Admin',
            'email' => 'superadmin@angular.com',
        ],[
            'password' => Hash::make("123123123"),
            'status' => 1
        ]);
        if(!$admin->hasRole('Super Admin')){
            $admin->assignRole('Super Admin');
        }
    }
}
