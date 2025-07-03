<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Http\Controllers\LlmController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
    
    // Ruta para el componente de chat LLM
    Route::get('/chat', App\Livewire\ChatComponent::class)->name('chat');
});

// Grupo de rutas LLM con múltiples capas de seguridad
Route::middleware(['auth', 'verified', 'llm.security'])->group(function () {
    // Ruta para generación de contenido con rate limiting combinado
    Route::post('/llm/generate', [LlmController::class, 'generateContent'])
        ->middleware(['throttle:llm', 'throttle:llm-hourly', 'throttle:llm-daily'])
        ->name('llm.generate');
    
    // Ruta para análisis con rate limiting más estricto
    Route::post('/llm/analyze', [LlmController::class, 'analyzeContent'])
        ->middleware(['throttle:llm-analysis', 'throttle:llm-hourly', 'throttle:llm-daily'])
        ->name('llm.analyze');
});

require __DIR__.'/auth.php';
