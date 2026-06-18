<?php

namespace App\Services;

class InterviewEvaluationService
{
    // Rubric max values — must match your prompt exactly
    private const RUBRIC_MAX = [
        'accuracy'        => 40,
        'completeness'    => 15,
        'technical_depth' => 20,
        'clarity'         => 20,
        'examples'        => 5,
    ];

    // Final weights — must sum to 1.0
    private const WEIGHTS = [
        'accuracy'        => 0.45,
        'completeness'    => 0.25,
        'technical_depth' => 0.20,
        'clarity'         => 0.08,
        'examples'        => 0.02,
    ];

    public function __construct(
        private AnswerValidatorService $validator,
        private AnswerQualityService $quality,
        private PromptBuilderService $promptBuilder,
        private GroqService $groq,
       
        private RuleEvaluationService $rules
    ) {}

    public function evaluate(
        string $question,
        string $answer,
        string $difficulty = 'medium'
    ): array {

        \Log::info('=== USING GROQ SERVICE ==='); 

        if ($this->validator->isInvalid($answer)) {
            return [
                'score'           => 0,
                'feedback'        => 'No valid answer provided.',
                'improved_answer' => 'Try explaining the concept instead of skipping.',
                'source'          => 'groq',
            ];
        }

        $quality = $this->quality->detect($answer);

        try {
            $prompt = $this->promptBuilder->build($question, $answer, $difficulty);
            $raw = $this->groq->generate($prompt);
            $json   = json_decode($raw['response'] ?? '', true);

            if (!is_array($json)) {
                throw new \Exception('Invalid AI response');
            }

            // ── 1. Read raw rubric scores ────────────────────────────────
            $accuracy        = (int) ($json['accuracy']        ?? 0);
            $clarity         = (int) ($json['clarity']         ?? 0);
            $technicalDepth  = (int) ($json['technical_depth'] ?? 0);
            $completeness    = (int) ($json['completeness']    ?? 0);
            $examples        = (int) ($json['examples']        ?? 0);

            // ── 2. Normalize each score to 0–100 BEFORE weighting ────────
            //    This is the main fix: accuracy/40 × 100, completeness/15 × 100, etc.
            $norm = [
                'accuracy'        => ($accuracy        / self::RUBRIC_MAX['accuracy'])        * 100,
                'completeness'    => ($completeness    / self::RUBRIC_MAX['completeness'])    * 100,
                'technical_depth' => ($technicalDepth  / self::RUBRIC_MAX['technical_depth']) * 100,
                'clarity'         => ($clarity         / self::RUBRIC_MAX['clarity'])         * 100,
                'examples'        => ($examples        / self::RUBRIC_MAX['examples'])        * 100,
            ];

 

            // ── 3. Weighted sum (now correctly sums to 0–100) ───────────
            $finalScore = 0;
            foreach (self::WEIGHTS as $key => $weight) {
                $finalScore += $norm[$key] * $weight;
            }

            // ── 4. Difficulty multiplier (replaces your old boost logic) ─
            $difficultyMultiplier = match ($difficulty) {
                'easy' => 1.05,   // slight curve up — easy questions forgive minor gaps
                'hard' => 0.95,   // slight curve down — hard questions demand more
                default => 1.0,
            };
            $finalScore *= $difficultyMultiplier;

            // ── 5. Penalize vague wording ────────────────────────────────
            $vagueWords = ['maybe', 'i think', 'probably', 'sort of', 'kind of'];
            foreach ($vagueWords as $word) {
                if (str_contains(strtolower($answer), $word)) {
                    $finalScore -= 4; // was 5, slightly softer
                }
            }

            // ── 6. Hard ceiling for very short answers ───────────────────
            $wordCount = str_word_count($answer);
            if ($wordCount < 5) {
                $finalScore = min($finalScore, 30);
            }

            // ── 7. Concept coverage check ────────────────────────────────
            $coreConcepts = [
                'cors'    => ['cross-origin', 'resource sharing', 'http', 'browser', 'origin'],
                'rest api'=> ['http', 'request', 'response', 'stateless'],
                'mvc'     => ['model', 'view', 'controller'],
                'orm'     => ['database', 'model', 'object', 'sql'],
                'session' => ['session', 'server', 'login', 'user'],
            ];

            $questionLower = strtolower($question);
            $answerLower   = strtolower($answer);

            foreach ($coreConcepts as $topic => $concepts) {
                if (str_contains($questionLower, $topic)) {
                    $matches  = count(array_filter($concepts, fn($c) => str_contains($answerLower, $c)));
                    $coverage = $matches / count($concepts);
                    if ($coverage < 0.2) {
                        $finalScore = min($finalScore, 35);
                    }
                }
            }

            // ── 8. "What is" definition check ────────────────────────────
            if (str_starts_with($questionLower, 'what is')) {
                $hasDefinition = (bool) array_filter(
                    ['is a', 'refers to', 'stands for', 'means', 'is used', 'is software', 'is a type'],
                    fn($i) => str_contains($answerLower, $i)
                );
                if (!$hasDefinition) {
                    $finalScore = min($finalScore, 40);
                }
            }

            // ── 9. Weak answer ceiling ───────────────────────────────────
            if ($quality === 'weak') {
                $finalScore = min($finalScore, 55);
            }

            // ── 10. Clamp and round ──────────────────────────────────────
            $finalScore = (int) round(max(0, min(100, $finalScore)));

            return [
                'score'          => $finalScore,
                'rubric'         => [
                    'accuracy'        => $accuracy,
                    'clarity'         => $clarity,
                    'technical_depth' => $technicalDepth,
                    'completeness'    => $completeness,
                    'examples'        => $examples,
                    'normalized'      => array_map(fn($v) => round($v), $norm), // useful for debugging
                ],
                'feedback'        => $json['feedback']        ?? '',
                'improved_answer' => $json['improved_answer'] ?? '',
                'quality'         => $quality,
                'source'          => 'ollama',
            ];

        } catch (\Throwable $e) {
            \Log::error('InterviewEval fell back to rules', ['error' => $e->getMessage()]);
            return $this->rules->evaluate($question, $answer);
        }
    }
}