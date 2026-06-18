<?php

namespace App\Services;

class AnswerValidatorService
{
    public function isInvalid(string $answer): bool
    {
        $clean = strtolower(trim($answer));

        $invalid = [
            'idk',
            'i don\'t know',
            'i dont know',
            'no idea',
            'unknown',
            'n/a',
            'na',
            '',
            '...'
        ];

        if (in_array($clean, $invalid)) {
            return true;
        }

        // too short = not valid
        if (strlen($clean) < 6) {
            return true;
        }

        return false;
    }
}