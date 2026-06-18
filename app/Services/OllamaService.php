<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OllamaService
{
    public function generate(string $prompt): array
    {
        $baseUrl = rtrim(config('services.ollama.base_url'), '/');
        $model = config('services.ollama.model');

        $response = Http::timeout(120)->post("{$baseUrl}/api/generate", [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false,
            'format' => 'json',
            'keep_alive' => '15m',
            'options' => [
                'temperature' => 0.1,
                'num_predict' => 300,
            ],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException("Ollama failed");
        }

        return $response->json();
    }
}