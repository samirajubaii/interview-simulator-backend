<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'question',
        'keywords',
        'difficulty'
    ];

    protected $casts = [
        'keywords' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}