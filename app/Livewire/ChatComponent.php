<?php

namespace App\Livewire;

use App\Services\LlmService;
use Livewire\Component;

class ChatComponent extends Component
{
    public string $message = '';
    public string $analyzeMessage = '';

    public string $response = '';
    public string $analyzeResponse = '';

    public function sendMessage(LlmService $llmService)
    {
        $this->response = $llmService->generateText($this->message);
        $this->message = '';
    }

    public function analyzeContent(LlmService $llmService)
    {
        $this->analyzeResponse = $llmService->analyzeContent($this->analyzeMessage);
        $this->analyzeMessage = '';
    }

    public function render()
    {
        return view('livewire.chat-component');
    }
}
