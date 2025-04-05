<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogController extends Controller
{
    /**
     * Pobierz zawartość pliku logów z opcjonalnym filtrowaniem
     */
    public function getLogs(Request $request)
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (!file_exists($logPath)) {
            return response('Plik logów nie istnieje.', 404);
        }
        
        // Usunięto logowanie dostępu do logów
        
        $logs = file_get_contents($logPath);
        
        // Filtrowanie i formatowanie logów
        $filter = $request->query('filter');
        $search = $request->query('search');
        
        if ($filter || $search) {
            $logLines = explode("\n", $logs);
            $filteredLines = [];
            
            foreach ($logLines as $line) {
                $matchesFilter = true;
                $matchesSearch = true;
                
                // Filtrowanie według poziomu (INFO, ERROR, WARNING, itp.)
                if ($filter && $filter !== 'ALL') {
                    $matchesFilter = Str::contains($line, ".$filter:");
                }
                
                // Filtrowanie według wyszukiwanej frazy
                if ($search) {
                    $matchesSearch = Str::contains(strtolower($line), strtolower($search));
                }
                
                if ($matchesFilter && $matchesSearch) {
                    $filteredLines[] = $line;
                }
            }
            
            $logs = implode("\n", $filteredLines);
        }
        
        // Formatowanie logów dla lepszej czytelności
        $logs = $this->formatLogs($logs);
        
        return response($logs);
    }
    
    /**
     * Formatuj logi dla lepszej czytelności
     */
    private function formatLogs($logs)
    {
        // Dodaj kolorowanie dla różnych poziomów logów
        $logs = preg_replace('/\.INFO:/', '<span class="text-blue-500 font-bold">.INFO:</span>', $logs);
        $logs = preg_replace('/\.ERROR:/', '<span class="text-red-500 font-bold">.ERROR:</span>', $logs);
        $logs = preg_replace('/\.WARNING:/', '<span class="text-yellow-500 font-bold">.WARNING:</span>', $logs);
        $logs = preg_replace('/\.NOTICE:/', '<span class="text-purple-500 font-bold">.NOTICE:</span>', $logs);
        $logs = preg_replace('/\.DEBUG:/', '<span class="text-green-500 font-bold">.DEBUG:</span>', $logs);
        
        // Dodaj podświetlenie dla dat
        $logs = preg_replace('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', '<span class="text-gray-400">$0</span>', $logs);
        
        return $logs;
    }
    
    /**
     * Wyczyść plik logów
     */
    public function clearLogs()
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (file_exists($logPath)) {
            // Utwórz kopię zapasową logów przed wyczyszczeniem
            $backupPath = storage_path('logs/laravel_backup_' . now()->format('Y-m-d_H-i-s') . '.log');
            copy($logPath, $backupPath);
            
            // Wyczyść plik logów
            file_put_contents($logPath, '');
            
            // Zaloguj czyszczenie logów
            Log::info('Logi zostały wyczyszczone', [
                'user_id' => auth()->id(),
                'user_ip' => request()->ip(),
                'backup_path' => $backupPath,
                'time' => now()->format('Y-m-d H:i:s')
            ]);
            
            return response()->json([
                'status' => 'success', 
                'message' => 'Logi zostały wyczyszczone',
                'backup_path' => $backupPath
            ]);
        }
        
        return response()->json(['status' => 'error', 'message' => 'Plik logów nie istnieje'], 404);
    }
    
    /**
     * Pobierz statystyki logów
     */
    public function getLogStats()
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (!file_exists($logPath)) {
            return response()->json(['status' => 'error', 'message' => 'Plik logów nie istnieje'], 404);
        }
        
        $logs = file_get_contents($logPath);
        $logLines = explode("\n", $logs);
        
        // Liczenie wystąpień różnych poziomów logów
        $stats = [
            'total' => count(array_filter($logLines)),
            'info' => substr_count($logs, '.INFO:'),
            'error' => substr_count($logs, '.ERROR:'),
            'warning' => substr_count($logs, '.WARNING:'),
            'notice' => substr_count($logs, '.NOTICE:'),
            'debug' => substr_count($logs, '.DEBUG:'),
            'size' => filesize($logPath),
            'last_modified' => date('Y-m-d H:i:s', filemtime($logPath))
        ];
        
        return response()->json(['status' => 'success', 'stats' => $stats]);
    }
}
