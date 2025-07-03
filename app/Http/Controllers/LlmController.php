<?php

namespace App\Http\Controllers;

use App\Services\LlmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LlmController extends Controller
{
    public function __construct(
        private LlmService $llmService
    ) {}

    public function generateContent(Request $request)
    {
        // Validación estricta de entrada
        $validated = $request->validate([
            'prompt' => [
                'required',
                'string',
                'max:2000', // Límite de caracteres
                'min:3',
                'regex:/^[a-zA-Z0-9\s\.,!?¿¡;:()\-_áéíóúüñÁÉÍÓÚÜÑ]+$/', // Caracteres seguros incluyendo español
            ]
        ], [
            'prompt.required' => 'El prompt es requerido.',
            'prompt.max' => 'El prompt no puede exceder 2000 caracteres.',
            'prompt.min' => 'El prompt debe tener al menos 3 caracteres.',
            'prompt.regex' => 'El prompt contiene caracteres no permitidos.',
        ]);

        // Sanitizar entrada
        $prompt = strip_tags($validated['prompt']);
        $prompt = preg_replace('/[<>"\']/', '', $prompt);
        $prompt = trim($prompt);

        // Logging de seguridad
        Log::info('LLM Generate API accessed', [
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email,
            'prompt_length' => strlen($prompt),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()
        ]);

        try {
            $response = $this->llmService->generateText($prompt);
            
            // Log successful generation
            Log::info('LLM content generated successfully', [
                'user_id' => auth()->id(),
                'response_length' => strlen($response),
            ]);

            return response()->json([
                'success' => true,
                'content' => $response
            ]);
            
        } catch (\Exception $e) {
            // Log error
            Log::error('LLM generation failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'prompt_length' => strlen($prompt)
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al generar contenido. Intenta nuevamente.'
            ], 500);
        }
    }

    public function analyzeContent(Request $request)
    {
        // Validación estricta de entrada
        $validated = $request->validate([
            'content' => [
                'required',
                'string',
                'max:5000', // Mayor límite para análisis
                'min:10',
                'regex:/^[a-zA-Z0-9\s\.,!?¿¡;:()\-_áéíóúüñÁÉÍÓÚÜÑ\n\r]+$/', // Permitir saltos de línea para análisis
            ]
        ], [
            'content.required' => 'El contenido a analizar es requerido.',
            'content.max' => 'El contenido no puede exceder 5000 caracteres.',
            'content.min' => 'El contenido debe tener al menos 10 caracteres.',
            'content.regex' => 'El contenido contiene caracteres no permitidos.',
        ]);

        // Sanitizar entrada
        $content = strip_tags($validated['content']);
        $content = preg_replace('/[<>"\']/', '', $content);
        $content = trim($content);

        // Logging de seguridad
        Log::info('LLM Analyze API accessed', [
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email,
            'content_length' => strlen($content),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()
        ]);

        try {
            $response = $this->llmService->analyzeContent($content);
            
            // Log successful analysis
            Log::info('LLM analysis completed successfully', [
                'user_id' => auth()->id(),
                'response_length' => strlen($response),
            ]);

            return response()->json([
                'success' => true,
                'analysis' => $response
            ]);
            
        } catch (\Exception $e) {
            // Log error
            Log::error('LLM analysis failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'content_length' => strlen($content)
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al analizar contenido. Intenta nuevamente.'
            ], 500);
        }
    }
}
