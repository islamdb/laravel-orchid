<?php


namespace App\Support;


use Illuminate\Support\Str;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\TD;
use ReflectionClass;

class Column
{
    /**
     * @param $name
     * @param null $title
     * @param array $labels
     * @return \Orchid\Screen\Cell|TD
     */
    public static function boolean($name, $title = null, array $labels = [true => __('Yes'), false => __('No')])
    {
        $title = is_null($title)
            ? Str::title(str_replace('_', ' ', $name))
            : $title;

        return TD::make($name, $title)
            ->sort()
            ->render(function ($model) use ($labels, $name) {
                return $labels[$model->{$name}];
            });
    }

    /**
     * @param $name
     * @param null $title
     * @param string $locale
     * @return \Orchid\Screen\Cell|TD
     */
    public static function dateTime($name, $title = null, $locale = 'id', $withTime = true, $withDayName = true)
    {
        return static::text($name, $title)
            ->render(function ($model) use ($locale, $name, $withTime, $withDayName){
                return readable_datetime($model->{$name}, $locale, $withTime, $withDayName);
            })
            ->filter(TD::FILTER_DATE);
    }

    /**
     * @param $name
     * @param null $title
     * @return \Orchid\Screen\Cell|TD
     */
    public static function money($name, $title = null)
    {
        return static::text($name, $title)
            ->render(function ($model) use ($name) {
                return number_format(
                    $model->{$name},
                    2,
                    ',',
                    '.'
                );
            })
            ->filter(TD::FILTER_NUMERIC);
    }

    /**
     * @param $name
     * @param null $title
     * @param bool $sorting
     * @param string $filter
     * @return \Orchid\Screen\Cell|TD
     */
    public static function text($name, $title = null, $sorting = true, $filter = TD::FILTER_TEXT)
    {
        $title = is_null($title)
            ? Str::title(str_replace('_', ' ', $name))
            : $title;

        $td = TD::make($name, $title);
        $td = $sorting
            ? $td->sort()
            : $td;
        $td = empty($filter)
            ? $td
            : $td->filter($filter);

        return $td;
    }

    /**
     * @param $name
     * @param null $title
     * @param int $deep
     * @return \Orchid\Screen\Cell|TD
     * @throws \ReflectionException
     */
    public static function shortcut($name, $title = null, $deep = 2)
    {
        $resource = Str::plural(
            Str::snake(
                (new ReflectionClass(
                    debug_backtrace(
                        DEBUG_BACKTRACE_PROVIDE_OBJECT,
                        $deep
                    )[$deep - 1]['object']
                ))->getShortName(),
                '-'
            )
        );

        return self::text($name, $title)
            ->render(function ($model) use ($resource, $name) {
                return Link::make($model->{$name})
                    ->route('platform.resource.edit', [
                        'resource' => $resource,
                        'id' => $model->id ?? $model->key
                    ]);
            });
    }
}
