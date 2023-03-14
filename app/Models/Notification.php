<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    public $table = 'notifications';
    protected $fillable = ['id', 'user_id', 'post_id', 'event', 'read'];
    
}
