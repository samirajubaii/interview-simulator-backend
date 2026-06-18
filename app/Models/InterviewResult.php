<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InterviewResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'difficulty',  // add this
        'category_id', // add this (for role)
        'score',
        'total_questions',
        'skipped',
        'answers',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'user_id' => 'integer',
        'score' => 'integer',
        'total_questions' => 'integer',
        'skipped' => 'integer',
        'answers' => 'array',
    ];
}