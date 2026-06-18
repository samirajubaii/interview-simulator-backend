<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\QuestionController; 
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\InterviewResultController;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use App\Models\Category;

Route::post('/evaluate-warmup', function() {
    try {
        $client = new \GuzzleHttp\Client(['timeout' => 5]);
        
        $client->post('http://localhost:11434/api/generate', [
            'json' => [
                'model' => 'qwen2.5:3b',
                'prompt' => 'test',
                'stream' => false,
                'options' => ['num_predict' => 1]
            ]
        ]);
        
        return response()->json(['status' => 'success']);
    } catch (\Exception $e) {
        return response()->json(['status' => 'skipped'], 200);
    }
});

Route::middleware('api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/results', [InterviewResultController::class, 'store']);
    Route::get('/results', [InterviewResultController::class, 'index']);
    Route::get('/results/{id}', [InterviewResultController::class, 'show']);

    
Route::get('/questions', [QuestionController::class, 'index']);
Route::post('/evaluate', [InterviewResultController::class, 'evaluate']);
Route::get('/evaluate/status/{id}', [InterviewResultController::class, 'evaluationStatus']);
Route::get('/categories', function () {
    return cache()->remember('categories', now()->addHours(24), function () {
        return Category::all();
    });
});
Route::post('/questions/generate', function (Request $request) {
    $validated = $request->validate([
        'role'       => 'required|string|in:Frontend,Backend,Fullstack,DevOps',
        'difficulty' => 'required|string|in:easy,medium,hard',
    ]);

    try {
        $generator = app(\App\Services\QuestionGeneratorService::class);
        $questions = $generator->generate($validated['role'], $validated['difficulty']);

        return response()->json([
            'questions' => $questions,
            'source'    => 'ai',
        ]);
    } catch (\Throwable $e) {
        \Log::error('Question generation failed', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to generate questions'], 500);
    }
});

});     
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', function (Request $request) {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));
        return response()->json(['message' => 'If that email exists, a reset link has been sent.']);
    });

    Route::post('/reset-password', function (Request $request) {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->tokens()->delete();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password reset successfully.'])
            : response()->json(['message' => 'Invalid or expired token.'], 422);
    });
});

