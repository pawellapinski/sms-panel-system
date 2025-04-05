<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Wyświetl monitor SMS-ów
     */
    public function smsMonitor()
    {
        return view('dashboard.sms-monitor');
    }

    /**
     * Wyświetl listę SMS-ów
     */
    public function smsList()
    {
        return view('dashboard.sms-list');
    }

    /**
     * Wyświetl logi systemu
     */
    public function logs()
    {
        return view('dashboard.logs');
    }
    
    /**
     * Wyświetl ustawienia
     */
    public function settings()
    {
        return view('dashboard.settings');
    }
}
