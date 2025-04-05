<?php
ini_set('max_execution_time', 30); // standardowe 30 sekund

// Wszystkie deklaracje use na początku pliku
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use App\Http\Controllers\SmsWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\SmsMessage;

// Główna strona
Route::get('/', function () {
    return view('welcome');
});

// Trasy związane z dashboardem
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard/sms-monitor', function () {
    return view('dashboard.sms-monitor');
})->middleware(['auth'])->name('dashboard.sms-monitor');

Route::get('/dashboard/sms-list', function () {
    return view('dashboard.sms-list');
})->middleware(['auth'])->name('dashboard.sms-list');

Route::get('/dashboard/logs', function () {
    return view('dashboard.logs');
})->middleware(['auth'])->name('dashboard.logs');

// Endpoint do pobierania logów
Route::get('/api/logs', function () {
    $logPath = storage_path('logs/laravel.log');
    
    if (!file_exists($logPath)) {
        return response('Plik logów nie istnieje.', 404);
    }
    
    $logs = file_get_contents($logPath);
    return response($logs);
});

// Endpoint do czyszczenia logów
Route::post('/api/logs/clear', function () {
    $logPath = storage_path('logs/laravel.log');
    
    if (file_exists($logPath)) {
        file_put_contents($logPath, '');
        return response()->json(['status' => 'success', 'message' => 'Logi zostały wyczyszczone']);
    }
    
    return response()->json(['status' => 'error', 'message' => 'Plik logów nie istnieje'], 404);
})->middleware('auth');

// Endpoint do usuwania SMS-a
Route::delete('/api/sms/{id}', function ($id) {
    try {
        $sms = \App\Models\SmsMessage::findOrFail($id);
        
        // Zapisz informacje o SMS-ie przed usunięciem
        $smsData = [
            'id' => $sms->id,
            'phone_number' => $sms->phone_number,
            'message' => $sms->message,
            'received_at' => $sms->received_at,
        ];
        
        // Usuń SMS
        $sms->delete();
        
        // Zaloguj usunięcie
        Log::info('SMS został usunięty', $smsData);
        
        return response()->json(['status' => 'success', 'message' => 'SMS został usunięty']);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
    }
})->middleware('auth');

// API do pobierania ostatniego SMS-a
Route::get('/api/last-sms', function () {
    $lastSms = \App\Models\SmsMessage::latest('received_at')->first();
    
    if ($lastSms) {
        return response()->json([
            'status' => 'success',
            'sms' => $lastSms
        ]);
    } else {
        return response()->json([
            'status' => 'error',
            'message' => 'Brak SMS-ów w bazie danych'
        ]);
    }
});

// API do pobierania listy SMS-ów
Route::get('/api/sms-list', function (Request $request) {
    $limit = $request->input('limit', 100);
    $page = $request->input('page', 1);
    
    $total = \App\Models\SmsMessage::count();
    $totalPages = ceil($total / $limit);
    
    $messages = \App\Models\SmsMessage::orderBy('received_at', 'desc')
                          ->skip(($page - 1) * $limit)
                          ->take($limit)
                          ->get();
    
    return response()->json([
        'status' => 'success',
        'messages' => $messages,
        'total' => $total,
        'totalPages' => $totalPages,
        'currentPage' => $page
    ]);
});

// Endpoint do sprawdzania statusu serwera
Route::get('/api/server-status', function () {
    return response()->json(['status' => 'online', 'timestamp' => now()]);
});

// Dodaję endpoint /status, który jest używany w widoku sms-monitor.blade.php
Route::get('/status', function () {
    return response()->json([
        'status' => 'running',
        'time' => now()->format('Y-m-d H:i:s'),
        'server_ip' => request()->server('SERVER_ADDR'),
        'client_ip' => request()->ip()
    ]);
});

// Trasy związane z profilem użytkownika
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
