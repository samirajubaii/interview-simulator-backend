<?php

namespace App\Services;

class AnswerQualityService
{
    public function detect(string $answer): string
    {
        $answer = strtolower($answer);

        $words = preg_split('/\W+/', $answer);
        $wordCount = count(array_filter($words));

        $hasTechnicalWords = preg_match('/(api|database|http|sql|mvc|model|controller|server)/', $answer);

        if ($wordCount < 5) {
            return 'invalid';
        }

        if ($wordCount < 15 && !$hasTechnicalWords) {
            return 'weak';
        }

        if ($wordCount < 30) {
            return 'medium';
        }

        return 'strong';
    }
}