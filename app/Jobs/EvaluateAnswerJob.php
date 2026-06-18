<?php

namespace App\Jobs;

use App\Models\InterviewResult;
use App\Services\GroqService;
use App\Services\PromptBuilderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EvaluateAnswerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public string $evaluationId,
        public string $question,
        public string $answer,
        public string $difficulty = 'medium'
    ) {}

    public function handle(GroqService $groq, PromptBuilderService $promptBuilder): void
    {
        try {
            $prompt = $promptBuilder->build($this->question, $this->answer, $this->difficulty);
            $json   = $this->callGroq($groq, $prompt, $promptBuilder);

            if ($json === null) {
                throw new \Exception('Groq returned no valid JSON after retries');
            }

            $accuracy       = (int) ($json['accuracy']        ?? 0);
            $clarity        = (int) ($json['clarity']         ?? 0);
            $technicalDepth = (int) ($json['technical_depth'] ?? 0);
            $completeness   = (int) ($json['completeness']    ?? 0);
            $examples       = (int) ($json['examples']        ?? 0);

            $finalScore =
                ($accuracy       / 40) * 100 * 0.45 +
                ($completeness   / 15) * 100 * 0.25 +
                ($technicalDepth / 20) * 100 * 0.20 +
                ($clarity        / 20) * 100 * 0.08 +
                ($examples       / 5)  * 100 * 0.02;

            $finalScore = (int) round(max(0, min(95, $finalScore)));

            $feedbackText = $json['feedback'] ?? '';
            if (preg_match('/\[[\w\s]+\]/', $feedbackText)) {
                $trimmed      = strtolower(trim($this->answer));
                $feedbackText = strlen($trimmed) < 10
                    ? 'No meaningful answer provided.'
                    : 'Unable to generate specific feedback.';
            }

            cache()->put("evaluation:{$this->evaluationId}", [
                'status'          => 'done',
                'score'           => $finalScore,
                'feedback'        => $feedbackText,
                'improved_answer' => $json['improved_answer'] ?? '',
                'source'          => 'groq',
                'provisional'     => false,
            ], now()->addMinutes(30));

        } catch (\Throwable $e) {
            Log::error('EvaluateAnswerJob failed', ['error' => $e->getMessage()]);

            cache()->put("evaluation:{$this->evaluationId}", [
                'status'          => 'done',
                'score'           => 50,
                'feedback'        => 'AI grading temporarily unavailable. Score is provisional.',
                'improved_answer' => '',
                'source'          => 'provisional',
                'provisional'     => true,
            ], now()->addMinutes(30));
        }
    }

    private function callGroq(GroqService $groq, string $fullPrompt, PromptBuilderService $promptBuilder): ?array
    {
        $attempts = [
            [$fullPrompt, 3],
            [$promptBuilder->buildCompact($this->question, $this->answer), 2],
        ];

        foreach ($attempts as [$prompt, $tries]) {
            for ($i = 0; $i < $tries; $i++) {
                try {
                    $raw  = $groq->generate($prompt);
                    $json = $groq->parseEvaluationJson($raw['response'] ?? '');
                    if ($json !== null) return $json;
                } catch (\Throwable $e) {
                    Log::warning('Groq attempt failed', ['error' => $e->getMessage()]);
                }
            }
        }

        return null;
    }
}