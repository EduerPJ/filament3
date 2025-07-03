<?php

namespace App\Services;

use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\Text\Response;

class LlmService
{
    /**
     * Generar texto usando LLM
     */
    public function generateText(string $prompt, string $model = 'gpt-4o-mini'): string
    {
        return Prism::text()
            ->using(Provider::OpenAI, $model)
            ->withPrompt($prompt)
            ->asText()
            ->text;
    }

    /**
     * Analizar contenido
     */
    public function analyzeContent(string $content, string $model = 'gpt-4o-mini'): string
    {
        return Prism::text()
            ->using(Provider::OpenAI, $model)
            ->withPrompt("Analiza el siguiente contenido: {$content}")
            ->asText()
            ->text;
    }

    /**
     * Generar respuesta de chat
     */
    public function chatResponse(string $message, string $model = 'gpt-4o-mini'): string
    {
        return Prism::text()
            ->using(Provider::OpenAI, $model)
            ->withPrompt("Responde de manera útil y amigable: {$message}")
            ->asText()
            ->text;
    }

    /**
     * Generar texto con configuración personalizada
     */
    public function generateWithConfig(string $prompt, array $config = []): string
    {
        $provider = $config['provider'] ?? Provider::OpenAI;
        $model = $config['model'] ?? Provider::OpenAI;
        $providerConfig = $config['provider_config'] ?? [];

        $prism = Prism::text()->using($provider, $model);

        if (!empty($providerConfig)) {
            $prism->usingProviderConfig($providerConfig);
        }

        return $prism->withPrompt($prompt)->asText()->text;
    }
}
