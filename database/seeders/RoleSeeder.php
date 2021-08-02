<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Orchid\Platform\Models\Role;
use Orchid\Support\Facades\Dashboard;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            'super-administrator',
            'administrator'
        ];

        foreach ($roles as $key => $role) {
            $attr = [
                'name' => ucwords($role),
                'slug' => Str::slug($role),
                'permissions' => [
                    'platform.index' => true,
                    'platform.systems.attachment' => true
                ]
            ];

            switch ($role)
            {
                case 'super-administrator':
                    $attr['permissions'] = Dashboard::getAllowAllPermission();
                    break;
            }

            Role::create($attr);
        }
    }
}
