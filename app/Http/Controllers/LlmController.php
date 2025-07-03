<?php

namespace App\Http\Controllers;

use App\Services\LlmService;
use Illuminate\Http\Request;

class LlmController extends Controller
{
    public function __construct(
        private LlmService $llmService
    ) {}

    public function generateContent(Request $request)
    {
        $prompt = $request->input('prompt');
        $response = $this->llmService->generateText($prompt);

        return response()->json(['content' => $response]);
    }

    public function analyzeContent(Request $request)
    {
        $content = $request->input('content');
        $response = $this->llmService->analyzeContent($content);

        return response()->json(['analysis' => $response]);
    }
}
