<?php

namespace App\Http\Controllers;

use App\Models\InterviewResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InterviewResultController extends Controller
{
    public function store(Request $request)
{
    $validated = $request->validate([
        'session_id'      => 'nullable|string|max:255',
        'difficulty'      => 'nullable|string|in:easy,medium,hard',
        'category_id'     => 'nullable|string|max:255',
        'score'           => 'required|integer|min:0|max:100',
        'total_questions' => 'required|integer|min:1',
        'skipped'         => 'required|integer|min:0',
        'answers'         => 'nullable|array',
    ]);

    $result = InterviewResult::create([
        ...$validated,
        'user_id' => $request->user()->id,
    ]);

    // Clear cache so dashboard shows fresh results
    cache()->forget("user:{$request->user()->id}:results");

    return response()->json($result, 201);
}

public function index(Request $request)
{
    $userId = $request->user()->id;
    $cacheKey = "user:{$userId}:results";

    return cache()->remember($cacheKey, now()->addMinutes(10), function () use ($userId) {
        return InterviewResult::query()
            ->where('user_id', $userId)
            ->latest()
            ->get();
    });
}

    public function evaluate(Request $request)
{
    $validated = $request->validate([
        'question' => 'required|string',
        'answer'   => 'required|string',
    ]);

    try {
        $groq          = app(\App\Services\GroqService::class);
        $promptBuilder = app(\App\Services\PromptBuilderService::class);

        $prompt = $promptBuilder->build(
            $validated['question'],
            $validated['answer'],
            $validated['difficulty'] ?? 'medium'
        );

        $json = $this->callGroq($groq, $prompt, $validated['question'], $validated['answer']);

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
            $trimmed      = strtolower(trim($validated['answer']));
            $feedbackText = strlen($trimmed) < 10
                ? 'No meaningful answer provided. See the improved answer below for what to cover.'
                : 'Unable to generate specific feedback. See the improved answer below.';
        }

        return response()->json([
            'score'           => $finalScore,
            'feedback'        => $feedbackText,
            'improved_answer' => $json['improved_answer'] ?? '',
            'source'          => 'groq',
            'provisional'     => false,
        ]);

    } catch (\Throwable $e) {
        Log::error('Groq evaluation failed', ['error' => $e->getMessage()]);
        return response()->json(
            $this->fallback($validated['question'], $validated['answer'])
        );
    }
}

    public function evaluationStatus(string $id)
{
    $result = cache()->get("evaluation:{$id}");

    if (!$result) {
        return response()->json(['status' => 'not_found'], 404);
    }

    return response()->json($result);
}

    /**
     * Retry logic: full prompt x3, then compact prompt x2.
     */
    private function callGroq(
        \App\Services\GroqService $groq,
        string $fullPrompt,
        string $question,
        string $answer
    ): ?array {
        $promptBuilder = app(\App\Services\PromptBuilderService::class);

        $attempts = [
            [$fullPrompt, 3],
            [$promptBuilder->buildCompact($question, $answer), 2],
        ];

        foreach ($attempts as [$prompt, $tries]) {
            for ($i = 0; $i < $tries; $i++) {
                try {
                    $raw  = $groq->generate($prompt);
                    $json = $groq->parseEvaluationJson($raw['response'] ?? '');
                    if ($json !== null) {
                        return $json;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Groq attempt failed', ['error' => $e->getMessage()]);
                }
            }
        }

        return null;
    }

    /**
     * Provisional fallback when Groq is completely unavailable.
     */
    private function fallback(string $question, string $answer): array
    {
        $trimmed = strtolower(trim($answer));
        $trivial = ['idk', 'i dont know', "i don't know", 'no idea', 'not sure', 'dunno', 'pass'];
        $score   = (in_array($trimmed, $trivial) || strlen($trimmed) < 5) ? 15 : 50;

        $msg = "AI grading temporarily unavailable. Score ({$score}%) is provisional — resubmit for a full evaluation.";

        return [
            'score'           => $score,
            'feedback'        => $msg,
            'improved_answer' => '',
            'source'          => 'provisional',
            'provisional'     => true,
        ];
    }

    public function show(Request $request, $id)
{
    \Log::info('SHOW HIT', ['id' => $id, 'user' => $request->user()->id]);
    
    $result = InterviewResult::findOrFail($id);
    
    \Log::info('RESULT', ['result_user_id' => $result->user_id, 'request_user_id' => $request->user()->id]);

    if ((int) $result->user_id !== (int) $request->user()->id) {
        return response()->json(['message' => 'Forbidden.'], 403);
    }

    return response()->json($result);
}
}