<?php

namespace App\Orchid\Screens;

use App\Models\Setting;
use App\Orchid\Layouts\Listeners\SettingTypeListenerLayout;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
                ->modalTitle(__('Create'))
                ->method('store')
                ->modal('createOrEdit')
                ->asyncParameters(['setting' => null])
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
            ->get()
            ->map(function ($setting) {
                $setting->options = json_decode($setting->options);

                return $setting;
            });

        $layouts = [
            Layout::tabs(
                $settings->groupBy('group')
                    ->map(function ($group, $key) {
                        $group = $group->sortBy('position')->values();
                        $settingCount = $group->count();

                        return Layout::rows(
                            $group->map(function (Setting $setting, $key) use ($settingCount) {
                                $group = [
                                    Button::make('Save')
                                        ->icon('check')
                                        ->method('save')
                                        ->parameters([
                                            '_clicked' => $setting->key
                                        ])
                                        ->type(Color::SUCCESS()),
                                    ModalToggle::make('Properties')
                                        ->icon('pencil')
                                        ->type(Color::INFO())
                                        ->modalTitle(__('Edit').' '.$setting->name)
                                        ->method('update')
                                        ->modal('createOrEdit')
                                        ->asyncParameters(['setting' => $setting->key]),
                                    Button::make('Delete')
                                        ->icon('close')
                                        ->type(Color::DANGER())
                                        ->method('delete')
                                        ->confirm(__('Delete') . ' ' . $setting->name)
                                        ->parameters([
                                            '_clicked' => $setting->key
                                        ])
                                ];

                                $up = Button::make('')
                                    ->icon('arrow-up-circle')
                                    ->method('upDown')
                                    ->parameters([
                                        '_clicked' => $setting->key,
                                        '_up_down' => '<'
                                    ]);
                                $down = Button::make('')
                                    ->icon('arrow-down-circle')
                                    ->method('upDown')
                                    ->parameters([
                                        '_clicked' => $setting->key,
                                        '_up_down' => '>'
                                    ]);

                                if ($key == 0) {
                                    $group[] = $down;
                                } elseif ($key == $settingCount - 1) {
                                    $group[] = $up;
                                } else {
                                    $group[] = $up;
                                    $group[] = $down;
                                }

                                $fields = [
                                    Input::make($setting->key . '.old_value')
                                        ->type('hidden')
                                        ->value($setting->value),
                                    $setting->field(),
                                    Label::make('')
                                        ->value("setting('$setting->key')")
                                        ->style('font-family: "Courier New", monospace; font-size: smaller'),
                                    Group::make($group)
                                        ->autoWidth(),
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
            ),
            Layout::modal('createOrEdit', [
                SettingTypeListenerLayout::class
            ])->method('update')
                ->applyButton(__('Save'))
                ->size(Modal::SIZE_LG)
                ->title('Update Setting')
                ->async('asyncGetData')
        ];

        return $layouts;
    }

    public function asyncGetData($setting)
    {
        return [
            'setting' => Setting::query()->find($setting)
        ];
    }

    public function asyncField($key = null, $group = null, $name = null, $type = null, $description = null, $exists = null)
    {
        return SettingTypeListenerLayout::process($key, $group, $name, $type, $description, $exists);
    }

    public function upDown()
    {
        rescue(function () {
            DB::beginTransaction();

            $currentSetting = Setting::query()
                ->select(['key', 'group', 'position'])
                ->find(request()->_clicked);
            $toSwitchSetting = Setting::query()
                ->select(['key', 'group', 'position'])
                ->where('group', $currentSetting->group)
                ->where('position', request()->_up_down, $currentSetting->position)
                ->orderBy('position', (request()->_up_down == '<' ? 'desc' : 'asc'))
                ->first();

            $_ = $currentSetting->position;
            $currentSetting->position = $toSwitchSetting->position;
            $toSwitchSetting->position = $_;
            $currentSetting->save();
            $toSwitchSetting->save();

            DB::commit();

            Alert::success(__('Saved'));
        }, function ($e) {
            Alert::error(__('Failed').'. '.$e->getMessage());
        });
    }

    public function update()
    {
        $this->validate(request(), [
            'key' => 'required',
            'name' => 'required',
            'group' => 'required',
            'type' => 'required',
            'old_key' => 'required'
        ]);

        $setting = Setting::query()
            ->find(request()->old_key);

        if (!empty($setting)) {
            $data = request()->all();
            $data['options'] = json_encode($data['options']);

            $setting->fill($data);
            $setting->save();

            Alert::success($setting->name . ' ' . __('updated'));
        } else {
            Alert::warning(__('Setting was not found'));
        }
    }

    public function delete()
    {
        $setting = Setting::query()->find(request()->_clicked);
        if (!empty($setting)) {
            $setting->delete();

            Alert::success($setting->name . ' ' . __('deleted'));
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

            Alert::success($setting->name . ' ' . __('saved'));
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
        $data['position'] = Setting::query()->max('position') + 1;

        Setting::query()
            ->create($data);

        Alert::success(__('Setting was created.'));
    }
}
