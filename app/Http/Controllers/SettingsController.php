<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    /**
     * Zapisz ustawienia SMS Gateway
     */
    public function saveSmsSettings(Request $request)
    {
        try {
            // Walidacja danych wejściowych
            $validated = $request->validate([
                'local_server_address' => 'required|string',
                'local_username' => 'required|string',
                'local_password' => 'required|string',
            ]);
            
            // Ścieżka do pliku .env
            $envPath = base_path('.env');
            
            // Odczytaj zawartość pliku .env
            $envContents = File::get($envPath);
            
            // Aktualizuj zmienne środowiskowe
            $envContents = $this->updateEnvVariable($envContents, 'SMS_LOCAL_SERVER', $validated['local_server_address']);
            $envContents = $this->updateEnvVariable($envContents, 'SMS_LOCAL_USERNAME', $validated['local_username']);
            $envContents = $this->updateEnvVariable($envContents, 'SMS_LOCAL_PASSWORD', $validated['local_password']);
            
            // Logowanie zmiany ustawień
            Log::info('Zmieniono ustawienia Local Server', [
                'user_id' => auth()->id(),
                'local_server' => $validated['local_server_address'],
                'time' => now()->format('Y-m-d H:i:s')
            ]);
            
            // Zapisz zaktualizowaną zawartość do pliku .env
            File::put($envPath, $envContents);
            
            // Wyczyść cache konfiguracji
            $this->clearConfigCache();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Ustawienia zostały zapisane pomyślnie'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Błąd podczas zapisywania ustawień SMS', [
                'error' => $e->getMessage(),
                'time' => now()->format('Y-m-d H:i:s')
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Wystąpił błąd podczas zapisywania ustawień: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Aktualizuj zmienną w pliku .env
     */
    private function updateEnvVariable($envContents, $key, $value)
    {
        // Sprawdź czy zmienna już istnieje
        if (preg_match("/^{$key}=.*/m", $envContents)) {
            // Aktualizuj istniejącą zmienną
            return preg_replace("/^{$key}=.*/m", "{$key}=\"{$value}\"", $envContents);
        } else {
            // Dodaj nową zmienną na końcu pliku
            return $envContents . "\n{$key}=\"{$value}\"";
        }
    }
    
    /**
     * Wyczyść cache konfiguracji
     */
    private function clearConfigCache()
    {
        // W środowisku produkcyjnym możemy użyć komendy Artisan
        if (app()->environment('production')) {
            \Artisan::call('config:clear');
        }
    }
}
