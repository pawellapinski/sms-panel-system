<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand as BaseServeCommand;

class ServeCommand extends BaseServeCommand
{
    protected function serverProcess($hasEnvironment)
    {
        set_time_limit(0); // Wyłącz limit czasu wykonania
        ignore_user_abort(true); // Ignoruj przerwanie połączenia
        
        return parent::serverProcess($hasEnvironment);
    }
} 