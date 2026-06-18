<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GroqService
{
    public function generate(string $prompt): array
    {
        $key = config('services.groq.key');

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => "Bearer {$key}",
                'Content-Type'  => 'application/json',
            ])
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => config('services.groq.model', 'llama-3.3-70b-versatile'),
                'temperature' => 0.1,
                'max_tokens'  => 600,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => 'You return only valid JSON. No markdown fences or extra text.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException("Groq failed: " . $response->body());
        }

        $text = $response->json('choices.0.message.content') ?? '';

        return ['response' => trim($text)];
    }

    /**
     * Extract evaluation JSON from model output (handles markdown fences and extra prose).
     */
    public function parseEvaluationJson(string $text): ?array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);
        if ($this->isValidEvaluationPayload($decoded)) {
            return $decoded;
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end > $start) {
            $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
            if ($this->isValidEvaluationPayload($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function isValidEvaluationPayload(mixed $decoded): bool
    {
        return is_array($decoded)
            && array_key_exists('accuracy', $decoded)
            && array_key_exists('feedback', $decoded);
    }
}