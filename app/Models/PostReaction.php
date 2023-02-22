<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostReaction extends Model
{
    use HasFactory;
    public $table = 'post_reactions';
    protected $fillable = ['post_id', 'user_id'];
}
