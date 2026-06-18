<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request; // ✅ THIS WAS MISSING

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Question::with('category');

    if ($request->has('category_id')) {
        $query->where('category_id', $request->category_id);
    }

    if ($request->has('difficulty')) {
        $query->where('difficulty', $request->difficulty);
    }

    $limit = min(10, max(1, (int) $query->count()));

    return $query
        ->inRandomOrder()
        ->take($limit)
        ->get();
    }
    
}