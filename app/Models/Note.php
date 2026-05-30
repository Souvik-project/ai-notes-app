<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'tags',
        'summary',
        'search_vector',
    ];

    protected $casts = [
        'tags' => 'array',
        'search_vector' => 'array',
    ];
}
