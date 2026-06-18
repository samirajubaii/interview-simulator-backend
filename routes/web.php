<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/test-questions', function () {
    $generator = app(\App\Services\QuestionGeneratorService::class);
    $questions = $generator->generate('Frontend', 'medium');
    return response()->json($questions);
});
Route::get('/test-evaluate', function () {
    $groq = app(\App\Services\GroqService::class);
    $prompt = app(\App\Services\PromptBuilderService::class)->build(
        'Design a RESTful API endpoint for creating a new user.',
        'POST /api/users creates a new user. I would validate input and return 400 for invalid data or 409 if email exists. Password is hashed before storing. Returns 201 on success. Protected with JWT middleware.',
        'medium'
    );
    $raw = $groq->generate($prompt);
    return response()->json([
        'raw' => $raw['response'],
        'parsed' => json_decode($raw['response'], true),
    ]);
});