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
    
    // Rutas para el servicio LLM
    Route::post('/llm/generate', [LlmController::class, 'generateContent'])->name('llm.generate');
    Route::post('/llm/analyze', [LlmController::class, 'analyzeContent'])->name('llm.analyze');
});

require __DIR__.'/auth.php';
