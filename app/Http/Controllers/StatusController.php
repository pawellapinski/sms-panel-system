<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StatusController extends Controller
{
    /**
     * Pobierz status serwera (używany przez /api/server-status)
     */
    public function getServerStatus()
    {
        return response()->json([
            'status' => 'online', 
            'timestamp' => now()
        ]);
    }
    
    /**
     * Pobierz szczegółowy status serwera (używany przez /status)
     */
    public function getDetailedStatus()
    {
        return response()->json([
            'status' => 'running',
            'time' => now()->format('Y-m-d H:i:s'),
            'server_ip' => request()->server('SERVER_ADDR'),
            'client_ip' => request()->ip()
        ]);
    }
}
