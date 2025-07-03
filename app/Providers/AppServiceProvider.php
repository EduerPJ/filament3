<?php

namespace App\Providers;

use App\Services\LlmService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LlmService::class, function ($app) {
            return new LlmService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Rate limiter para APIs LLM - Crítico para seguridad
        RateLimiter::for('llm', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->user()->id ?? $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'error' => 'Demasiadas solicitudes. Intenta nuevamente en unos minutos.',
                        'retry_after' => 60
                    ], 429);
                });
        });

        // Rate limiter más estricto para análisis de contenido (más costoso)
        RateLimiter::for('llm-analysis', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->user()->id ?? $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'error' => 'Límite de análisis alcanzado. Intenta nuevamente en unos minutos.',
                        'retry_after' => 60
                    ], 429);
                });
        });

        // Rate limiter por hora para prevenir abuso
        RateLimiter::for('llm-hourly', function (Request $request) {
            return Limit::perHour(50)
                ->by($request->user()->id ?? $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'error' => 'Límite horario de solicitudes LLM alcanzado.',
                        'retry_after' => 3600
                    ], 429);
                });
        });

        // Rate limiter diario para control de costos
        RateLimiter::for('llm-daily', function (Request $request) {
            return Limit::perDay(200)
                ->by($request->user()->id ?? $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'error' => 'Límite diario de solicitudes LLM alcanzado. Intenta mañana.',
                        'retry_after' => 86400
                    ], 429);
                });
        });
    }
}
