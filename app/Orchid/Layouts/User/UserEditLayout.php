<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\User;

use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Rows;

class UserEditLayout extends Rows
{
    /**
     * Views.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            Cropper::make('attachment')
                ->title('avatar')
                ->width(200)
                ->targetId()
                ->value($this->query->get('user')->attachment()->orderByDesc('created_at')->first()->id)
                ->height(200),

            Input::make('user.name')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Name'))
                ->placeholder(__('Name')),

            Input::make('user.email')
                ->type('email')
                ->required()
                ->title(__('Email'))
                ->placeholder(__('Email')),
        ];
    }
}
