<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;
    public $table = 'posts';
    protected $fillable = ['user_id', 'group_id', 'status', 'content', 'images', 'type'];
    protected $casts = [
        'images' => 'array'
    ];
}
