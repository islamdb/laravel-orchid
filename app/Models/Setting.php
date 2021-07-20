<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Orchid\Attachment\Attachable;
use Orchid\Attachment\Models\Attachment;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Code;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Picture;
use Orchid\Screen\Fields\RadioButtons;
use Orchid\Screen\Fields\Range;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Upload;
use Reflection;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use SplFileInfo;
use Throwable;

/**
 * @property string $type type
 * @property string $name name
 * @property string $description description
 * @property string $value value
 * @property string $options options
 */
class Setting extends Model
{
    /**
     * Database table name
     */
    protected $table = 'settings';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'key';

    /**
     * Mass assignable columns
     */
    protected $fillable = [
        'key',
        'type',
        'name',
        'group',
        'order',
        'description',
        'value',
        'options',
        'is_array_value'
    ];

    /**
     * Date time columns.
     */
    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * Required methods
     */
    const REQUIRED_METHODS = [
        RadioButtons::class => [
            'options' => "['one' => 'One', 'two' => 'Two', 'three' => 'Three']"
        ], Range::class => [
            'min' => "1",
            'max' => "100",
            'step' => "1"
        ], Select::class => [
            'options' => "['one' => 'One', 'two' => 'Two', 'three' => 'Three']"
        ]
    ];

    protected static function booted()
    {
        static::deleting(function (Setting $setting) {
            $setting->attachment()
                ->get()
                ->each(function (Attachment $attachment) {
                    $attachment->delete();
                });
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Support\Collection
     */
    public function attachment()
    {
        if (in_array($this->attributes['type'], [Upload::class, Picture::class, Cropper::class]) and !empty($this->attributes['value'])) {
            $attachmentIds = is_array($this->attributes['value'])
                ? $this->attributes['value']
                : json_decode($this->attributes['value']);
            $attachmentIds = is_array($attachmentIds)
                ? $attachmentIds
                : [$attachmentIds];

            return Attachment::query()
                ->whereIn('id', $attachmentIds);
        }

        return collect([]);
    }

    /**
     * @param null $typeClass
     * @return \Illuminate\Support\Collection
     */
    public static function types($typeClass = null)
    {
        $types = collect(File::allFiles(base_path('vendor/orchid/platform/src/Screen/Fields')))
            ->filter(function (SplFileInfo $file) {
                $isPhpFile = Str::endsWith($file->getFilename(), '.php');

                return $isPhpFile;
            })
            ->map(function (SplFileInfo $file) {
                $className = 'Orchid\Screen\Fields\\' . str_replace('.php', '', $file->getBasename());
                $class = new ReflectionClass($className);

                return $class;
            })
            ->filter(function (ReflectionClass $class) use ($typeClass) {
                if (is_bool($class->getParentClass())) {
                    return false;
                }

                $filter = ($class->getParentClass()->getName() == 'Orchid\Screen\Field'
                    and !in_array($class->getShortName(), [
                        'Relation',
                        'ViewField',
                        'Label',
                        'Radio',
                        'Password',
                        'Range'
                    ]));

                if (empty($typeClass)) {
                    return $filter;
                } elseif (is_array($typeClass)) {
                    return ($filter and in_array($class->getName(), $typeClass));
                }

                return ($filter and $class->getName() == $typeClass);
            })
            ->map(function (ReflectionClass $class) {
                $comment = file_get_contents($class->getFileName()) . $class->getParentClass()->getDocComment();
                $isThereRequiredMethod = in_array($class->getName(), array_keys(static::REQUIRED_METHODS));
                $requiredMethods = $isThereRequiredMethod
                    ? static::REQUIRED_METHODS[$class->getName()]
                    : [];

                $methods = collect(explode("\n", $comment))
                    ->filter(function ($line) {
                        return str_contains($line, '@method ')
                            and !Str::contains($line, ['title(', 'help(']);
                    })
                    ->map(function ($line) use ($isThereRequiredMethod, $requiredMethods) {
                        $method = method_from_doc_code($line);
                        $method['active'] = false;
                        if ($isThereRequiredMethod and in_array($method['name'], array_keys($requiredMethods))) {
                            $method['active'] = true;
                            $method['param_str'] = $requiredMethods[$method['name']];
                        }

                        return (object)$method;
                    });
                collect($class->getMethods())
                    ->filter(function (ReflectionMethod $method) {
                        $isSelfReturnType = false;
                        if ($method->hasReturnType()) {
                            $isSelfReturnType = $method->getReturnType()->getName() == 'self';
                        }

                        return Reflection::getModifierNames($method->getModifiers())[0] == 'public' and $isSelfReturnType;
                    })
                    ->each(function (ReflectionMethod $method) use ($isThereRequiredMethod, $requiredMethods, &$methods) {
                        $exists = $methods->where('name', '=',  $method->getName())
                                ->count() > 0;
                        if (!$exists) {
                            $filename = $method->getFileName();
                            $startLine = $method->getStartLine() - 1;
                            $source = file($filename);
                            $line = implode("", array_slice($source, $startLine, 1));

                            $method_ = method_from_doc_code($line);
                            $method_['active'] = false;
                            if ($isThereRequiredMethod and in_array($method->getName(), array_keys($requiredMethods))) {
                                $method_['active'] = true;
                                $method_['param_str'] = $requiredMethods[$method->getName()];
                            }

                            $methods->push((object)$method_);
                        }
                    });
                $methods = $methods->sortBy('name')
                    ->values();

                return (object)[
                    'name' => $class->getShortName(),
                    'class' => $class->getName(),
                    'methods' => $methods
                ];
            });

        return $types;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public static function options()
    {
        return self::types()
            ->mapWithKeys(function ($field) {
                return [$field->class => $field->name];
            });
    }

    /**
     * @param $type
     * @param string $name
     * @return mixed
     */
    public static function valueField($type, $name = 'value')
    {
        return rescue(function () use ($type, $name) {
            return $type::make($name);
        }, function () use ($name) {
            return TextArea::make($name);
        });
    }

    /**
     * @return mixed
     */
    public function field()
    {
        $field = Setting::valueField($this->attributes['type'], $this->attributes['key'] . '.new_value');
        $options = collect(is_array($this->attributes['options']) ? $this->attributes['options'] : json_decode($this->attributes['options']))
            ->where('active', '=', true)
            ->mapWithKeys(function ($option) {
                return [$option->name => $option->param];
            })
            ->toArray();

        $value = $this->attributes['value'];
        if ($this->attributes['is_array_value']) {
            if (!is_array($value)) {
                $value = json_decode($value, true);
            } else {
                $value = json_decode(json_encode($value), true);
            }
        }

        $field = chained_method_call($field, $options)
            ->title($this->attributes['name'])
            ->value($value)
            ->help($this->attributes['description']);

        return $field;
    }
}
