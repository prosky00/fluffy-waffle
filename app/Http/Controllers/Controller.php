<?php

namespace App\Http\Controllers;

use App\Models\FactionSetting;
use App\Models\NavLink;

abstract class Controller
{
    protected function settings(): FactionSetting
    {
        return FactionSetting::singleton();
    }

    protected function view(string $view, array $data = [])
    {
        return view($view, array_merge([
            'settings' => $this->settings(),
            'navLinks' => NavLink::ordered()->get(),
        ], $data));
    }
}
