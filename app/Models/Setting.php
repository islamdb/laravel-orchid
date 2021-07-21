<?php

namespace App\Models;

use App\Support\Field;
use Illuminate\Database\Eloquent\Model;
use Orchid\Attachment\Models\Attachment;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\Picture;
use Orchid\Screen\Fields\Upload;

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
        'position',
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
        if (in_array($this->attributes['type'], Field::FILE_FIELDS) and !empty($this->attributes['value'])) {
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
     * @return \Illuminate\Support\Collection
     */
    public static function options()
    {
        return Field::all(false)
            ->mapWithKeys(function ($field) {
                return [$field->class => $field->name];
            });
    }

    /**
     * @return mixed
     */
    public function field()
    {
        $options = collect(is_array($this->attributes['options']) ? $this->attributes['options'] : json_decode($this->attributes['options']))
            ->where('active', '=', true)
            ->mapWithKeys(function ($option) {
                return [$option->name => $option->param];
            })
            ->toArray();
        $field = Field::make($this->attributes['type'], $this->attributes['key'] . '.new_value', $options);

        $value = $this->attributes['value'];
        if ($this->attributes['is_array_value']) {
            if (!is_array($value)) {
                $value = json_decode($value, true);
            } else {
                $value = json_decode(json_encode($value), true);
            }
        }

        $field = $field->title($this->attributes['name'])
            ->value($value)
            ->help($this->attributes['description']);

        return $field;
    }
}
