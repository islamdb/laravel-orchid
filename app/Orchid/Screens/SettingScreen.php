<?php

namespace App\Orchid\Screens;

use App\Models\Setting;
use App\Orchid\Layouts\Listeners\SettingTypeListenerLayout;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Fields\Picture;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Layouts\Listener;
use Orchid\Screen\Layouts\Modal;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Support\Color;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;
use ReflectionClass;

class SettingScreen extends Screen
{
    /**
     * Display header name.
     *
     * @var string
     */
    public $name = 'Setting';

    /**
     * Display header description.
     *
     * @var string|null
     */
    public $description = 'Setting list of application';

    /**
     * Query data.
     *
     * @return array
     */
    public function query(): array
    {
        return [];
    }

    /**
     * Button commands.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): array
    {
        return [
            ModalToggle::make('Create')
                ->icon('plus')
                ->method('store')
                ->modal('createOrEdit')
        ];
    }

    /**
     * Views.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): array
    {
        $settings = Setting::query()
            ->latest()
            ->get()
            ->map(function ($setting) {
                $setting->options = json_decode($setting->options);

                return $setting;
            });

        $layouts = [
            Layout::modal('createOrEdit', [
                SettingTypeListenerLayout::class
            ])->method('storeOrUpdate')
                ->applyButton(__('Save'))
                ->size(Modal::SIZE_LG)
                ->title('Create Setting'),
            Layout::tabs(
                $settings->groupBy('group')
                    ->map(function ($group, $key) {
                        $group = $group->values();
                        $settingCount = $group->count();

                        return Layout::rows(
                            $group->map(function (Setting $setting, $key) use ($settingCount) {
                                $fields = [
                                    Input::make($setting->key.'.old_value')
                                        ->type('hidden')
                                        ->value($setting->value),
                                    $setting->field(),
                                    Label::make('')
                                        ->value('key: '.$setting->key)
                                        ->style('font-family: "Courier New", monospace; font-size: smaller'),
                                    Group::make([
                                        Button::make('Save')
                                            ->icon('check')
                                            ->method('save')
                                            ->parameters([
                                                '_clicked' => $setting->key
                                            ])
                                            ->type(Color::SUCCESS()),
                                        Button::make('Properties')
                                            ->icon('pencil')
                                            ->disabled()
                                            ->type(Color::PRIMARY()),
                                        Button::make('Delete')
                                            ->icon('close')
                                            ->type(Color::DANGER())
                                            ->method('delete')
                                            ->confirm(__('Delete').' '.$setting->name)
                                            ->parameters([
                                                '_clicked' => $setting->key
                                            ])
                                    ])->autoWidth(),
                                ];

                                if ($settingCount - 1 > $key) {
                                    $fields[] = Label::make('')->hr();
                                }

                                return $fields;
                            })->flatten(1)
                                ->toArray()
                        );
                    })
                    ->toArray()
            )
        ];

        return $layouts;
    }

    public function asyncField($key = null, $group = null, $name = null, $type = null, $description = null)
    {
        return SettingTypeListenerLayout::process($key, $group, $name, $type, $description);
    }

    public function delete()
    {
        $setting = Setting::query()->find(request()->_clicked);
        if (!empty($setting)) {
            $setting->delete();

            Alert::success($setting->name.' '.__('deleted'));
        } else {
            Alert::warning(__('Setting was not found'));
        }
    }

    public function save()
    {
        $settings = request()->all();

        $setting = Setting::query()->find($settings['_clicked']);
        if (!empty($setting)) {
            $value = $settings[$setting->key];
            if (is_array($value)) {
                $new = $value['new_value'] ?? null;
                $old = $value['old_value'] ?? null;
                $old = ($setting->is_array_value or is_array($new))
                    ? json_decode($old)
                    : $old;

                if ($new != $old) {
                    $isArrayValue = is_array($new);
                    $new = $isArrayValue
                        ? json_encode($new)
                        : $new;

                    $setting->update([
                        'is_array_value' => $isArrayValue,
                        'value' => $new
                    ]);
                }
            }

            Alert::success($setting->name.' '.__('saved'));
        } else {
            Alert::warning(__('Setting was not found'));
        }
    }

    public function store()
    {
        $this->validate(request(), [
            'key' => 'required',
            'name' => 'required',
            'group' => 'required',
            'type' => 'required'
        ]);

        $data = request()->all();
        $data['options'] = json_encode($data['options'] ?? []);

        Setting::query()
            ->create($data);

        Alert::success(__('Setting was created.'));
    }
}
