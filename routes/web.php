<?php
ini_set('max_execution_time', 30); // standardowe 30 sekund

// Wszystkie deklaracje use na początku pliku
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
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
Route::middleware(['auth', 'verified'])->group(function () {
    // Przekierowanie z /dashboard na /dashboard/sms-monitor
    Route::get('/dashboard', function() {
        return redirect()->route('dashboard.sms-monitor');
    })->name('dashboard');
    
    Route::get('/dashboard/sms-monitor', [DashboardController::class, 'smsMonitor'])->name('dashboard.sms-monitor');
    Route::get('/dashboard/sms-list', [DashboardController::class, 'smsList'])->name('dashboard.sms-list');
    Route::get('/dashboard/logs', [DashboardController::class, 'logs'])->name('dashboard.logs');
    Route::get('/dashboard/settings', [DashboardController::class, 'settings'])->name('dashboard.settings');
});

// Endpointy API związane z logami
Route::get('/api/logs', [LogController::class, 'getLogs']);
Route::get('/api/logs/stats', [LogController::class, 'getLogStats']);
Route::post('/api/logs/clear', [LogController::class, 'clearLogs'])->middleware('auth');

// Endpointy API związane z SMS-ami
Route::delete('/api/sms/{id}', [SmsController::class, 'destroy'])->middleware('auth');
Route::post('/api/sms/clear-all', [SmsController::class, 'clearAll'])->middleware('auth');
Route::get('/api/last-sms', [SmsController::class, 'getLastSms']);
Route::get('/api/sms-list', [SmsController::class, 'getSmsList']);

// Endpointy API związane ze statusem serwera
Route::get('/api/server-status', [StatusController::class, 'getServerStatus']);
Route::get('/status', [StatusController::class, 'getDetailedStatus']);

// Endpointy API związane z webhookami
Route::post('/api/check-webhooks', [WebhookController::class, 'checkWebhooks'])->middleware('auth');

// Endpointy API związane z ustawieniami
Route::post('/api/settings/save', [SettingsController::class, 'saveSmsSettings'])->middleware('auth');

// Trasy związane z profilem użytkownika
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
