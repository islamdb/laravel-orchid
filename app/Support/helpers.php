<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

if (!function_exists('route_name')) {
    /**
     * get route name by url
     *
     * @param $url
     * @return string|null
     */
    function route_name($url)
    {
        return Route::match([
            'get', 'post', 'delete', 'patch', 'put'
        ], $url)->getName();
    }
}

if (!function_exists('uuidstr')) {
    function uuidstr($delim = '-')
    {
        return str_replace('-', $delim, Str::uuid());
    }
}

if (!function_exists('query_statement')) {
    function query_statement($query, $dump = false)
    {
        $sql_str = $query->toSql();
        $bindings = $query->getBindings();

        $wrapped_str = str_replace('?', "'?'", $sql_str);

        return Str::replaceArray('?', $bindings, $wrapped_str);
    }
}

if (!function_exists('sub_query')) {
    function sub_query($query, $alias = null, $parenthesis = true)
    {
        $query = query_statement($query);
        $query = $parenthesis
            ? '(' . $query . ')'
            : $query;
        $query = is_null($alias)
            ? $query
            : $query . '`' . $alias . '`';

        return DB::raw($query);
    }
}

if (!function_exists('write_error')) {
    function write_error(Exception $exception)
    {
        if (!Schema::hasTable('errors')) {
            return null;
        }

        try {
            $userId = auth()
                ->id();
        } catch (Exception $exception) {
            $userId = null;
        }

        $errorId = DB::table('errors')
            ->insertGetId([
                'user_id' => $userId,
                'message' => $exception->getMessage(),
                'trace' => json_encode($exception->getTrace())
            ]);

        return $errorId;
    }
}

if (!function_exists('is_current_route')) {
    function is_current_route($routeName, $mustMatch = true)
    {
        if ($mustMatch) {
            return Route::currentRouteName() == $routeName;
        }

        return Str::contains(Route::currentRouteName(), $routeName);
    }
}

if (!function_exists('narrator')) {
    function narrator($text, $startReplace = '<b>', $endReplace = '</b>', $start = '[', $end = ']')
    {
        $texts = explode($start, $text);
        for ($c = 1; $c < count($texts); $c++) {
            if (Str::contains($texts[$c], $end)) {
                $texts[$c] = $startReplace . preg_replace("/$end/", $endReplace, $texts[$c], 1);
            }
        }

        return collect($texts)
            ->join('');
    }
}

if (!function_exists('lang')) {
    /**
     * get current language
     *
     * @param null $language
     * @return \Illuminate\Session\SessionManager|\Illuminate\Session\Store|mixed|null
     */
    function lang($language = null)
    {
        if (!empty($language)) {
            session()->put('lang', $language);
        }

        if (!session()->has('lang')) {
            session()->put('lang', 'id');
        }

        $language = session('lang')[0];
        $language = (strlen($language) == 1) ? session('lang') : $language;

        return $language;
    }
}

if (!function_exists('translate')) {
    function translate(string $sentence, string $from, string $to = null, bool $forceTranslation = false)
    {
        if (empty($sentence))
            return $sentence;

        $to = empty($to)
            ? lang()
            : $to;

        if ($from == $to)
            return $sentence;

        $md5 = md5($sentence);

        $translate = Dictionary::query()
            ->select('to')
            ->where('from_id', $from)
            ->where('to_id', $to)
            ->where('from', $md5)
            ->first();

        if (empty($translate)) {
            if (strlen($sentence) > 5000 and !$forceTranslation)
                return translate('Sentence is too long to translate', 'en');

            try {
                $translate = GoogleTranslate::trans($sentence, $to, $from);

                Dictionary::query()
                    ->create([
                        'from_id' => $from,
                        'to_id' => $to,
                        'from' => $md5,
                        'to' => $translate
                    ]);

                // Dictionary::flushQueryCache();

                return $translate;
            } catch (Exception $exception) {
                write_error($exception);

                return '<i>' . $sentence . '</i>';
            }
        }

        return $translate->to;
    }
}

if (!function_exists('clean_text')) {
    function clean_text($text)
    {
        $text = preg_replace('/[^A-Za-z0-9\- ]/', '', $text);

        return $text;
    }
}

if (!function_exists('nasab')) {
    function nasab($text)
    {
        $text = str_replace('binti', 'bin', $text);

        return array_reverse(explode('bin', $text));
    }
}

if (!function_exists('badge')) {
    /**
     * badge html
     *
     * @param $text
     * @param null $type
     * @return string
     */
    function badge($text, $type = null)
    {
        if (empty($type)) {
            $badges = collect(['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark']);
            $type = $badges->random();
        }

        $type = 'badge-' . $type;

        return '<span class="badge ' . $type . '">' . $text . '</span>';
    }
}

if (!function_exists('highlight')) {
    function highlight($text, $sentence, $match = true, $clean = true, $open = '<span style="background-color: yellow">', $close = '</span>')
    {
        if (empty($sentence))
            return $text;

        $sentence = $clean
            ? clean_text($sentence)
            : $sentence;

        $words = $match
            ? [$sentence]
            : explode(' ', $sentence);

        $pattern = '#(?<=^|\W)('
            . implode('|', array_map('preg_quote', $words))
            . ')(?=$|\W)#i';

        $text = preg_replace($pattern, "$open$1$close", $text);

        return $text;
    }
}

if (!function_exists('array_to_string')) {
    /**
     * convert list to readable
     *
     * @param $array
     * @param string $splitter
     * @param string $lastSplitter
     * @return string|string[]
     */
    function array_to_string($array, $splitter = ', ', $lastSplitter = ' and ', $prefix = '', $suffix = '')
    {
        $string = implode($splitter, $array);
        $string = str_replace($splitter . last($array), $lastSplitter . last($array), $string);

        return $prefix . $string . $suffix;
    }
}

if (!function_exists('livewire_routes')) {
    function livewire_routes($basePath = '', $makeRoute = true, $param = 'zx', $optionalParam = 'zz')
    {
        $param = strtolower($param);
        $optionalParam = strtolower($optionalParam);

        $routes = [];

        collect(File::allFiles(base_path('app/Http/Livewire/' . $basePath)))
            ->filter(function ($file) {
                return $file->isFile() and $file->getExtension() == 'php';
            })
            ->each(function ($file) use ($param, $optionalParam, $basePath, &$routes, $makeRoute) {
                $params = [];
                $optionalParams = [];

                $path = str_replace('/', '\\', $basePath) . '\\' . $file->getRelativePathname();
                $path = str_replace('.php', '', $path);
                $path = str_replace('/', '\\', $path);
                $path = collect(explode('\\', $path))
                    ->map(function ($p) {
                        return Str::snake($p, '-');
                    })
                    ->join('.');

                $uri = collect(explode('\\', str_replace('/', '\\', $file->getRelativePath())))
                    ->map(function ($path) use ($param, $optionalParam, &$params, &$optionalParams) {
                        $path = Str::snake($path);

                        $exploded = explode('_', $path, 2);
                        $checkParam = strtolower($exploded[0]);

                        if ($checkParam == $param) {
                            $path = '{' . $exploded[1] . '}';
                            $params[] = $exploded[1];
                        } elseif ($checkParam == $optionalParam) {
                            $path = '{' . $exploded[1] . '?}';
                            $optionalParams[] = $exploded[1];
                        }

                        return str_replace('_', '-', $path);
                    })
                    ->join('/');
                $uri = Str::endsWith($path, 'index')
                    ? $uri
                    : $uri . '/' . Str::of($path)
                        ->explode('.')
                        ->last();
                $uri = Str::of($uri)->startsWith('/')
                    ? substr($uri, 1)
                    : $uri;

                $component = 'App\Http\Livewire\\' . $basePath . '\\' . str_replace('/', '\\', $file->getRelativePathname());
                $component = str_replace('.php', '', $component);
                $component = str_replace('/', '\\', $component);

                $name = str_replace('/', '.', $uri);
                $name = str_replace('{', '', $name);
                $name = str_replace('}', '', $name);
                $name = str_replace('?', '', $name);
                $name = empty($name)
                    ? 'home'
                    : $name;

                $routes[] = [
                    'uri' => $uri,
                    'component' => $component,
                    'path' => $path,
                    'name' => $name,
                    'params' => $params,
                    'optional_params' => $optionalParams,
                    'filepath' => $file->getRelativePathName()
                ];

                if ($makeRoute)
                    Route::get($uri, $component)
                        ->name($name);
            });

        return $routes;
    }
}

if (!function_exists('html2text')) {
    function html2text($text)
    {
        return Html2Text::convert($text, ['ignore_errors' => true]);
    }
}

if (!function_exists('tajweed')) {
    function tajweed($text)
    {
        return (new AlQuranCloud\Tools\Parser\Tajweed())
            ->parse($text);
    }
}

if (!function_exists('setting')) {
    function setting($key, $default = null)
    {
        $setting = App\Models\Setting::query()
            ->find($key);

        if (empty($setting)) {
            return $default;
        }

        if (App\Support\Field::isFileField($setting->type)) {
            return $setting->attachment()->get();
        } elseif ($setting->is_array_value) {
            return rescue(function () use ($setting) {
                return json_decode($setting->value, true);
            }, function () use ($setting) {
                return $setting->value;
            });
        }

        return $setting->value;
    }
}

if (!function_exists('disable_foreign_key_checks')) {
    function disable_foreign_key_checks()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    }
}

if (!function_exists('enable_foreign_key_checks')) {
    function enable_foreign_key_checks()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}

if (!function_exists('push_to_table')) {
    function push_to_table($name, $data, $alias = [], $jsonColumn = null, $delete = true, $disableForeignCheck = true, $useTransaction = true, $dump = false)
    {
        try {
            $columns = collect(DB::select('desc ' . $name))->pluck('Field');

            if ($useTransaction) {
                DB::beginTransaction();
            }

            if ($disableForeignCheck) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            }

            if ($delete) {
                DB::table($name)->delete();
            }

            collect($data)->chunk(500)
                ->each(function ($chunk) use ($name, $columns, $alias, $jsonColumn) {
                    $chunk = collect($chunk)
                        ->map(function ($d) use ($columns, $alias, $jsonColumn) {
                            $d = (object)$d;

                            $row = collect($d)->only($columns)
                                ->filter(function ($r, $key) use ($alias) {
                                    return !in_array($key, array_keys($alias));
                                })
                                ->toArray();

                            foreach ($alias as $dkey => $tkey) {
                                $row[$tkey] = $d->{$dkey};
                            }

                            foreach (['created_at', 'updated_at'] as $item) {
                                if (!in_array($item, array_keys($row)) and in_array($item, $columns->toArray())) {
                                    $row[$item] = now();
                                }
                            }

                            if (!empty($jsonColumn)) {
                                $row[$jsonColumn] = json_encode($d);
                            }

                            return $row;
                        })
                        ->toArray();

                    DB::table($name)
                        ->insert($chunk);
                });

            if ($disableForeignCheck) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }

            if ($useTransaction) {
                DB::commit();
            }

            Log::info('Berhasil push to table', [
                'name' => $name,
                'alias' => $alias,
                'json_column' => $jsonColumn,
                'delete' => $delete,
                'disable_foreign_key_chek' => $disableForeignCheck
            ]);

            return true;
        } catch (Exception $e) {
            if ($useTransaction) {
                DB::rollBack();
            }

            Log::error('Gagal push to table. ' . $e->getMessage(), [
                'name' => $name,
                'alias' => $alias,
                'json_column' => $jsonColumn,
                'delete' => $delete,
                'disable_foreign_key_chek' => $disableForeignCheck,
                'trace' => collect($e->getTrace())->take(5)->toArray()
            ]);

            if ($dump) {
                dd([
                    'name' => $name,
                    'alias' => $alias,
                    'json_column' => $jsonColumn,
                    'delete' => $delete,
                    'disable_foreign_key_chek' => $disableForeignCheck,
                    'trace' => collect($e->getTrace())->take(5)->toArray()
                ]);
            }

            return false;
        }
    }
}

if (!function_exists('try_catch')) {
    function try_catch(callable $tryCallback, bool $useTransaction = true, bool $dump = false, callable $catchCallback = null)
    {
        try {
            if ($useTransaction) {
                DB::beginTransaction();
            }

            $returned = $tryCallback();

            if ($useTransaction) {
                DB::commit();
            }

            return $returned;
        } catch (Exception $e){
            if ($useTransaction) {
                DB::rollBack();
            }

            Log::error($e->getMessage(), collect($e->getTrace())->take(4)->toArray());
            report($e);

            if ($dump) {
                dd([
                    'message' => $e->getMessage(),
                    'trace' => $e->getTrace()
                ]);
            }

            if (!empty($catchCallback)) {
                return $catchCallback($e);
            }
        }
    }
}

if (!function_exists('readable_datetime')) {
    /**
     * Melakukan formatting tanggal
     *
     * @param Carbon $carbon
     * @return string
     */
    function readable_datetime($datetime, $locale = 'id', $withTime = true, $withDayName = true)
    {
        $format = 'dddd, MMMM Do YYYY, HH:mm:ss';
        $localeFormats = [
            'jv' => 'dddd, DD MMMM YYYY, HH:mm:ss',
            'id' => 'dddd, DD MMMM YYYY, HH:mm:ss',
            'en' => 'dddd, MMMM Do YYYY, HH:mm:ss'
        ];
        $format = $localeFormats[$locale] ?? $format;
        $format = !$withTime
            ? str_replace(', HH:mm:ss', '', $format)
            : $format;
        $format = !$withDayName
            ? str_replace('dddd, ', '', $format)
            : $format;

        return Carbon::parse($datetime)
            ->locale($locale)
            ->isoFormat($format);
    }
}

if (!function_exists('number_to_roman')) {
    function number_to_roman($number)
    {
        $map = [
            'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
            'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10,
            'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1
        ];
        $returnValue = '';
        while ($number > 0) {
            foreach ($map as $roman => $int) {
                if($number >= $int) {
                    $number -= $int;
                    $returnValue .= $roman;
                    break;
                }
            }
        }
        return $returnValue;
    }
}

if (!function_exists('number_to_alpha')) {
    function number_to_alpha($num)
    {
        return chr(substr("000".($num+65),-3));
    }
}

if (!function_exists('money_to_words')) {
    function money_to_words($money, $locale = 'id_ID', $decimalWordFrom = 'titik', $decimalWordTo = 'koma')
    {
        $formatter = new NumberFormatter('id_ID', NumberFormatter::SPELLOUT);

        return str_replace($decimalWordFrom, $decimalWordTo, $formatter->format($money));
    }
}

if (!function_exists('chained_method_call')) {
    function chained_method_call($object, $methods)
    {
        $callStr = 'return $object->';
        foreach($methods as $method => $param){
            $callStr.= "$method($param)->";
        }
        $callStr = substr($callStr, 0, -2);
        $callStr.= ';';

        return eval($callStr);
    }
}

if (!function_exists('method_from_doc_code')) {
    function method_from_doc_code($line)
    {
        $exploded = explode('(', $line);
        $exploded[1] = explode(')', $exploded[1])[0].')';

        $name = explode(' ', $exploded[0]);
        $name = end($name);

        $method = $name.'('.$exploded[1];

        $params = explode(',', str_replace(')', '', $exploded[1]));
        $params = collect($params)
            ->map(function ($param) use ($line, $exploded) {
                if (str_contains($param, '$')) {
                    $param = explode('$', $param);
                    $name = explode(' ', $param[1])[0];
                    $default = explode('=', $param[1]);
                    $default = count($default) == 1
                        ? null
                        : trim($default[1]);

                    return (object)[
                        'name' => $name,
                        'default' => $default
                    ];
                }

                return [
                    'name' => '',
                    'default' => ''
                ];
            })
            ->where('name', '!=', '');

        $paramStr = $params->pluck('default')->join(', ');

        return [
            'name' => $name,
            'params' => $params->toArray(),
            'param_str' => $paramStr,
            'full' => $method,
            'raw' => trim($line)
        ];
    }
}
