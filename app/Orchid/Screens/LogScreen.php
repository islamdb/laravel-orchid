<?php

namespace App\Orchid\Screens;

use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class LogScreen extends Screen
{
    /**
     * Display header name.
     *
     * @var string
     */
    public $name = 'Log';

    /**
     * Display header description.
     *
     * @var string|null
     */
    public $description = 'Log viewer';

    public $permission = [
        'platform.systems.log'
    ];

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
        return [];
    }

    /**
     * Views.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): array
    {
        return [
            Layout::view('log')
        ];
    }
}
