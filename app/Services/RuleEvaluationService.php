<?php

namespace App\Services;

class RuleEvaluationService
{
    public function evaluate(string $question, string $answer): array
    {
        $answer = strtolower($answer);

        $score = 30; // base fallback

        if (str_contains($answer, 'http') || str_contains($answer, 'api')) {
            $score += 30;
        }

        if (str_contains($answer, 'mvc') || str_contains($answer, 'database')) {
            $score += 20;
        }

        if (strlen($answer) > 100) {
            $score += 20;
        }

        return [
            'score' => min(100, $score),
            'feedback' => 'Rule-based fallback evaluation',
            'improved_answer' => 'Add more technical explanation and examples',
            'source' => 'rules'
        ];
    }
}