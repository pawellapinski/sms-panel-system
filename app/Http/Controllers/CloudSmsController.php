<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SmsMessage;

class CloudSmsController extends Controller
{
    protected $apiUrl = 'http://api.sms-gate.app:443';
    
    /**
     * Pobierz ostatni SMS z Cloud Server
     */
    public function getLastSms(Request $request)
    {
        try {
            // Pobranie danych uwierzytelniających z konfiguracji
            $username = config('sms.cloud.username');
            $password = config('sms.cloud.password');
            
            if (!$username || !$password) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Brak skonfigurowanych danych uwierzytelniających dla Cloud Server'
                ], 400);
            }
            
            // Wykonanie żądania do Cloud Server
            $response = Http::withBasicAuth($username, $password)
                ->get($this->apiUrl . '/api/sms', [
                    'limit' => 1
                ]);
            
            if (!$response->successful()) {
                Log::error('Błąd podczas pobierania SMS z Cloud Server', [
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                    'time' => now()->format('Y-m-d H:i:s')
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Nie udało się pobrać SMS-ów: ' . $response->status()
                ], 400);
            }
            
            $messages = $response->json();
            
            if (empty($messages)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Brak SMS-ów w Cloud Server'
                ]);
            }
            
            $lastSms = $messages[0];
            
            return response()->json([
                'status' => 'success',
                'sms' => $lastSms
            ]);
            
        } catch (\Exception $e) {
            Log::error('Wyjątek podczas pobierania SMS z Cloud Server', [
                'error' => $e->getMessage(),
                'time' => now()->format('Y-m-d H:i:s')
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Nie można połączyć się z Cloud Server: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Pobierz wszystkie SMS-y z Cloud Server
     */
    public function getAllSms(Request $request)
    {
        try {
            // Pobranie danych uwierzytelniających z konfiguracji
            $username = config('sms.cloud.username');
            $password = config('sms.cloud.password');
            
            if (!$username || !$password) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Brak skonfigurowanych danych uwierzytelniających dla Cloud Server'
                ], 400);
            }
            
            // Parametry paginacji
            $limit = $request->input('limit', 100);
            $page = $request->input('page', 1);
            
            // Wykonanie żądania do Cloud Server
            $response = Http::withBasicAuth($username, $password)
                ->get($this->apiUrl . '/api/sms', [
                    'limit' => $limit,
                    'offset' => ($page - 1) * $limit
                ]);
            
            if (!$response->successful()) {
                Log::error('Błąd podczas pobierania SMS-ów z Cloud Server', [
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                    'time' => now()->format('Y-m-d H:i:s')
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Nie udało się pobrać SMS-ów: ' . $response->status()
                ], 400);
            }
            
            $messages = $response->json();
            
            // Pobranie całkowitej liczby SMS-ów
            $countResponse = Http::withBasicAuth($username, $password)
                ->get($this->apiUrl . '/api/sms/count');
                
            $total = 0;
            if ($countResponse->successful()) {
                $total = $countResponse->json()['count'] ?? 0;
            }
            
            return response()->json([
                'status' => 'success',
                'messages' => $messages,
                'total' => $total,
                'totalPages' => ceil($total / $limit),
                'currentPage' => $page
            ]);
            
        } catch (\Exception $e) {
            Log::error('Wyjątek podczas pobierania SMS-ów z Cloud Server', [
                'error' => $e->getMessage(),
                'time' => now()->format('Y-m-d H:i:s')
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Nie można połączyć się z Cloud Server: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Zarejestruj webhook w Cloud Server
     */
    public function registerWebhook(Request $request)
    {
        try {
            // Pobranie danych uwierzytelniających z konfiguracji
            $username = config('sms.cloud.username');
            $password = config('sms.cloud.password');
            
            if (!$username || !$password) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Brak skonfigurowanych danych uwierzytelniających dla Cloud Server'
                ], 400);
            }
            
            // Walidacja danych wejściowych
            $validated = $request->validate([
                'webhook_url' => 'required|url'
            ]);
            
            // Wykonanie żądania do Cloud Server
            $response = Http::withBasicAuth($username, $password)
                ->post($this->apiUrl . '/api/webhook', [
                    'url' => $validated['webhook_url'],
                    'event' => 'sms:received'
                ]);
            
            if (!$response->successful()) {
                Log::error('Błąd podczas rejestracji webhooka w Cloud Server', [
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                    'time' => now()->format('Y-m-d H:i:s')
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Nie udało się zarejestrować webhooka: ' . $response->status()
                ], 400);
            }
            
            $webhook = $response->json();
            
            Log::info('Zarejestrowano webhook w Cloud Server', [
                'webhook_id' => $webhook['id'] ?? 'unknown',
                'webhook_url' => $validated['webhook_url'],
                'time' => now()->format('Y-m-d H:i:s')
            ]);
            
            return response()->json([
                'status' => 'success',
                'webhook' => $webhook,
                'message' => 'Webhook został zarejestrowany pomyślnie'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Wyjątek podczas rejestracji webhooka w Cloud Server', [
                'error' => $e->getMessage(),
                'time' => now()->format('Y-m-d H:i:s')
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Nie można połączyć się z Cloud Server: ' . $e->getMessage()
            ], 500);
        }
    }
}
