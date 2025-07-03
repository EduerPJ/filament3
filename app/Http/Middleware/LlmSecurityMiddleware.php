<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LlmSecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar que el usuario esté verificado
        if (!$request->user() || !$request->user()->hasVerifiedEmail()) {
            Log::warning('LLM access attempt by unverified user', [
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'route' => $request->route()?->getName()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Debes verificar tu email para usar las funciones de IA.'
            ], 403);
        }

        // Verificar User-Agent para prevenir bots maliciosos
        $userAgent = $request->userAgent();
        $suspiciousAgents = [
            'bot', 'crawler', 'spider', 'scraper', 
            'curl', 'wget', 'python', 'requests'
        ];

        foreach ($suspiciousAgents as $suspicious) {
            if (stripos($userAgent, $suspicious) !== false) {
                Log::warning('Suspicious user agent accessing LLM API', [
                    'user_agent' => $userAgent,
                    'user_id' => $request->user()->id,
                    'ip' => $request->ip()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Acceso no autorizado.'
                ], 403);
            }
        }

        // Verificar CSRF token explícitamente para APIs POST
        if ($request->isMethod('post')) {
            $token = $request->header('X-CSRF-TOKEN') ?: $request->input('_token');
            if (!$token || !hash_equals(session()->token(), $token)) {
                Log::warning('CSRF token mismatch in LLM API', [
                    'user_id' => $request->user()->id,
                    'ip' => $request->ip(),
                    'route' => $request->route()?->getName()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Token de seguridad inválido.'
                ], 403);
            }
        }

        // Verificar tamaño de la solicitud
        if ($request->getContent() && strlen($request->getContent()) > 50000) {
            Log::warning('Oversized LLM request detected', [
                'content_size' => strlen($request->getContent()),
                'user_id' => $request->user()->id,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'La solicitud es demasiado grande.'
            ], 413);
        }

        // Verificar intentos de inyección en headers
        $dangerousHeaders = ['X-Forwarded-For', 'X-Real-IP', 'X-Originating-IP'];
        foreach ($dangerousHeaders as $header) {
            if ($request->hasHeader($header)) {
                $headerValue = $request->header($header);
                if (preg_match('/[<>"\']|script|javascript|eval/i', $headerValue)) {
                    Log::warning('Malicious header detected in LLM request', [
                        'header' => $header,
                        'value' => $headerValue,
                        'user_id' => $request->user()->id
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'Solicitud maliciosa detectada.'
                    ], 400);
                }
            }
        }

        // Registrar acceso legítimo
        Log::info('LLM API access granted', [
            'user_id' => $request->user()->id,
            'route' => $request->route()?->getName(),
            'ip' => $request->ip(),
            'timestamp' => now()
        ]);

        return $next($request);
    }
}