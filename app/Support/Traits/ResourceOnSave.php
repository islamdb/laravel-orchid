<?php


namespace App\Support\Traits;


use Illuminate\Support\Str;

trait ResourceOnSave
{
    public function sluggable($request, $referencedColumn = 'title', $column = 'slug')
    {
        if (empty($request->{$column})){
            $request->merge([
                $column => Str::slug($request->{$referencedColumn})
            ]);
        }
    }

    public function saveWithAttachment($request, $model, $name = 'attachment')
    {
        $attachmentIds = $request->input($name, []);
        $request->request
            ->remove($name);

        parent::onSave($request, $model);

        $model->attachment()
            ->syncWithoutDetaching($attachmentIds);
    }
}
