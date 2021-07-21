<?php

namespace Database\Seeders;

use App\Models\Setting;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Factory::create('id_ID');
        $group = $faker->words(rand(2, 3));

        $data = [];
        Setting::types()
            ->values()
            ->each(function ($type, $key) use (&$data, &$faker, $group) {
                $data[] = [
                    'key' => Str::snake($type->name),
                    'type' => $type->class,
                    'group' => ucwords($group[rand(0, count($group) - 1)]),
                    'position' => $key + 1,
                    'name' => ucwords(Str::snake($type->name, ' ')),
                    'description' => $faker->sentence(rand(10, 20)),
                    'options' => $type->methods
                        ->map(function ($param) {
                            return [
                                'active' => $param->active,
                                'name' => $param->name,
                                'param' => $param->param_str,
                                'full' => $param->full
                            ];
                        })
                        ->toJson(),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            });
        Setting::query()
            ->insert($data);
    }
}
