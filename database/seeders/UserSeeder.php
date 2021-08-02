<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Orchid\Platform\Models\Role;
use Orchid\Support\Facades\Dashboard;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::create([
            'name' => 'Administrator',
            'username' => 'administrator',
            'email' => 'admin@email.com',
            'password' => bcrypt('password'),
            'permissions' => Dashboard::getAllowAllPermission(),
        ]);

        $user->roles()
            ->attach(
                Role::query()
                    ->where('slug', 'super-administrator')
                    ->first()
                    ->id
            );
    }
}
