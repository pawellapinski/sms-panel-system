<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Sprawdza zarejestrowane webhooki na serwerze SMS Gateway
     */
    public function checkWebhooks(Request $request)
    {
        // Walidacja danych wejściowych
        $validated = $request->validate([
            'ip_address' => 'required|string',
            'username' => 'required|string',
            'password' => 'required|string',
        ]);
        
        // Pobierz dane z żądania
        $ipAddress = $validated['ip_address'];
        $username = $validated['username'];
        $password = $validated['password'];
        
        // Upewnij się, że adres IP zawiera protokół http://
        if (!str_starts_with($ipAddress, 'http://') && !str_starts_with($ipAddress, 'https://')) {
            $ipAddress = 'http://' . $ipAddress;
        }
        
        // Usuń ewentualny trailing slash
        $ipAddress = rtrim($ipAddress, '/');
        
        try {
            // Wykonaj żądanie do serwera SMS Gateway
            $response = Http::withBasicAuth($username, $password)
                ->get($ipAddress . '/webhooks');
            
            // Sprawdź czy żądanie było udane
            if ($response->successful()) {
                // Pobierz dane z odpowiedzi
                $webhooks = $response->json();
                
                // Logowanie pomyślnego sprawdzenia webhooków
                Log::info('Sprawdzono webhooki', [
                    'user_id' => auth()->id(),
                    'ip_address' => $ipAddress,
                    'webhook_count' => count($webhooks),
                    'time' => now()->format('Y-m-d H:i:s')
                ]);
                
                // Zwróć dane webhooków
                return response()->json([
                    'status' => 'success',
                    'webhooks' => $webhooks
                ]);
            } else {
                // Logowanie błędu
                Log::error('Błąd podczas sprawdzania webhooków', [
                    'user_id' => auth()->id(),
                    'ip_address' => $ipAddress,
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                    'time' => now()->format('Y-m-d H:i:s')
                ]);
                
                // Zwróć informację o błędzie
                return response()->json([
                    'status' => 'error',
                    'message' => 'Błąd podczas pobierania webhooków: ' . $response->status() . ' ' . $response->body()
                ], 400);
            }
        } catch (\Exception $e) {
            // Logowanie wyjątku
            Log::error('Wyjątek podczas sprawdzania webhooków', [
                'user_id' => auth()->id(),
                'ip_address' => $ipAddress,
                'error' => $e->getMessage(),
                'time' => now()->format('Y-m-d H:i:s')
            ]);
            
            // Zwróć informację o błędzie
            return response()->json([
                'status' => 'error',
                'message' => 'Nie można połączyć się z serwerem: ' . $e->getMessage()
            ], 500);
        }
    }
}
