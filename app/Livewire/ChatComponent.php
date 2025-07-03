<?php

namespace App\Livewire;

use App\Services\LlmService;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class ChatComponent extends Component
{
    public string $message = '';
    public string $analyzeMessage = '';

    public string $response = '';
    public string $analyzeResponse = '';
    
    public bool $isLoading = false;
    public bool $isAnalyzing = false;
    
    public string $errorMessage = '';
    public string $analyzeErrorMessage = '';

    protected $rules = [
        'message' => [
            'required',
            'string',
            'max:2000',
            'min:3',
            'regex:/^[a-zA-Z0-9\s\.,!?¿¡;:()\-_áéíóúüñÁÉÍÓÚÜÑ]+$/'
        ],
        'analyzeMessage' => [
            'required',
            'string',
            'max:5000',
            'min:10',
            'regex:/^[a-zA-Z0-9\s\.,!?¿¡;:()\-_áéíóúüñÁÉÍÓÚÜÑ\n\r]+$/'
        ]
    ];

    protected $messages = [
        'message.required' => 'El mensaje es requerido.',
        'message.max' => 'El mensaje no puede exceder 2000 caracteres.',
        'message.min' => 'El mensaje debe tener al menos 3 caracteres.',
        'message.regex' => 'El mensaje contiene caracteres no permitidos.',
        
        'analyzeMessage.required' => 'El contenido a analizar es requerido.',
        'analyzeMessage.max' => 'El contenido no puede exceder 5000 caracteres.',
        'analyzeMessage.min' => 'El contenido debe tener al menos 10 caracteres.',
        'analyzeMessage.regex' => 'El contenido contiene caracteres no permitidos.',
    ];

    public function sendMessage(LlmService $llmService)
    {
        $this->resetErrorState();
        
        try {
            $this->validate(['message' => $this->rules['message']]);
            
            $this->isLoading = true;
            
            // Usar el método chatResponse que ya tiene protecciones
            $this->response = $llmService->chatResponse($this->message);
            
            // Registrar uso exitoso
            Log::info('Chat message processed successfully via Livewire', [
                'user_id' => auth()->id(),
                'message_length' => strlen($this->message),
                'response_length' => strlen($this->response)
            ]);
            
            $this->message = '';
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first();
            
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al enviar mensaje. Intenta nuevamente.';
            
            Log::error('Chat message failed via Livewire', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'message_length' => strlen($this->message ?? '')
            ]);
            
        } finally {
            $this->isLoading = false;
        }
    }

    public function analyzeContent(LlmService $llmService)
    {
        $this->resetAnalyzeErrorState();
        
        try {
            $this->validate(['analyzeMessage' => $this->rules['analyzeMessage']]);
            
            $this->isAnalyzing = true;
            
            // Usar el método analyzeContent que ya tiene protecciones
            $this->analyzeResponse = $llmService->analyzeContent($this->analyzeMessage);
            
            // Registrar análisis exitoso
            Log::info('Content analysis processed successfully via Livewire', [
                'user_id' => auth()->id(),
                'content_length' => strlen($this->analyzeMessage),
                'response_length' => strlen($this->analyzeResponse)
            ]);
            
            $this->analyzeMessage = '';
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->analyzeErrorMessage = collect($e->errors())->flatten()->first();
            
        } catch (\Exception $e) {
            $this->analyzeErrorMessage = 'Error al analizar contenido. Intenta nuevamente.';
            
            Log::error('Content analysis failed via Livewire', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'content_length' => strlen($this->analyzeMessage ?? '')
            ]);
            
        } finally {
            $this->isAnalyzing = false;
        }
    }

    public function clearResponse()
    {
        $this->response = '';
        $this->resetErrorState();
    }

    public function clearAnalysis()
    {
        $this->analyzeResponse = '';
        $this->resetAnalyzeErrorState();
    }

    private function resetErrorState()
    {
        $this->errorMessage = '';
        $this->resetErrorBag();
    }

    private function resetAnalyzeErrorState()
    {
        $this->analyzeErrorMessage = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.chat-component');
    }
}
