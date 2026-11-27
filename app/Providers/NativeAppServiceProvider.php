<?php

namespace App\Providers;

use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        Window::open()
            ->title(config('app.name', 'MyDevPortal'))
            ->width(1280)
            ->height(820)
            ->minWidth(400)
            ->minHeight(640)
            ->rememberState();
    }

    /**
     * Return an array of php.ini directives to be set.
     *
     * @return array<string, string>
     */
    public function phpIni(): array
    {
        return [
            'memory_limit' => '256M',
            'max_execution_time' => '0',
        ];
    }
}
