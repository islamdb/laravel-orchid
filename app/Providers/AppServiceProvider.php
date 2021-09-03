<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
//        setlocale(LC_ALL, 'id_ID.utf8');
//        config(['app.locale' => 'id']);
//        Carbon::setLocale('id');
//        date_default_timezone_set('Asia/Jakarta');
//        App::setLocale('id');

        ini_set('max_execution_time', 1200000);
        ini_set('post_max_size', '200M');
        ini_set('upload_max_filesize', '100M');

        require_once(__DIR__ . '/../Support/helpers.php');

        Collection::macro('onlyAttr', function ($keys) {
            $keys = is_array($keys)
                ? $keys
                : [$keys];

            return $this->map(function ($value) use ($keys) {
                return collect($value)
                    ->only($keys)
                    ->toArray();
            });
        });
    }
}
