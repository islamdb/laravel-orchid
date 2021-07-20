<?php

namespace App\Orchid\Layouts\Listeners;

use App\Models\Setting;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Code;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\RadioButtons;
use Orchid\Screen\Fields\Range;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Listener;
use Orchid\Support\Facades\Layout;
use ReflectionClass;

class SettingTypeListenerLayout extends Listener
{
    /**
     * List of field names for which values will be listened.
     *
     * @var string[]
     */
    protected $targets = [
        'key',
        'group',
        'name',
        'type',
        'description'
    ];

    /**
     * What screen method should be called
     * as a source for an asynchronous request.
     *
     * The name of the method must
     * begin with the prefix "async"
     *
     * @var string
     */
    protected $asyncMethod = 'asyncField';

    /**
     * @return Layout[]
     */
    protected function layouts(): array
    {
        return [
            Layout::columns([
                Layout::rows([
                    Input::make('key')
                        ->title('Key')
                        ->required(),
                    Input::make('name')
                        ->title('Name')
                        ->required()
                ]),
                Layout::rows([
                    Input::make('group')
                        ->title('Group')
                        ->required(),
                    Select::make('type')
                        ->title('Type')
                        ->options(Setting::options())
                        ->empty()
                        ->required()
                ])
            ]),
            Layout::rows([
                TextArea::make('description')
                    ->title('Description'),
                Matrix::make('options')
                    ->title('Options')
                    ->columns([
                        __('Active') => 'active',
                        __('Name') => 'name',
                        __('Parameter') => 'param',
                        __('Info') => 'full'
                    ])
                    ->fields([
                        'active' => CheckBox::make()
                            ->style('margin-left: 8px')
                            ->sendTrueOrFalse(),
                        'name' => Input::make(),
                        'param' => TextArea::make()->style('font-family: "Courier New", monospace;'),
                        'full' => Label::make()
                    ])
                    ->canSee(!empty($this->query->get('type')))
            ])
        ];
    }

    public static function process($key = null, $group = null, $name = null, $type = null, $description = null)
    {
        $options = Setting::types((new ReflectionClass(Setting::valueField($type)))->getName())
            ->first()
            ->methods
            ->map(function ($param) {
                return [
                    'active' => $param->active,
                    'name' => $param->name,
                    'param' => $param->param_str,
                    'full' => $param->full
                ];
            });

        return [
            'key' => $key,
            'group' => $group,
            'name' => $name,
            'type' => $type,
            'description' => $description,
            'options' => $options
        ];
    }
}
