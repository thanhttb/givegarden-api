<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;
    public $table = 'groups';
    protected $fillable = ['id', 'title', 'expired_at', 'status','description', 'open_at'];

    // public function alluser(){
    //     return $this->belongsToMany('App\Models\User', 'user_group', 'group_id', 'user_id')
    //         ->where('users.role', 'coach')
    //         ->orWhere('users.role', 'admin')
    //         ->orWhere('users.role', 'member')
    //         ->orWhere('users.role', 'supporter')
    //         ->using('App\Models\UserGroup');
    // }
    public function users(){
        return $this->belongsToMany('App\Models\User', 'user_group', 'group_id', 'user_id')
            ->where('users.role', 'member')
            ->using('App\Models\UserGroup');
    }
    public function coaches(){
        return $this->belongsToMany('App\Models\User', 'user_group', 'group_id', 'user_id')
        ->whereIn('users.role', ['admin', 'coach', 'supporter'])
        // ->orWhere('users.role', 'admin')
        // ->orWhere('users.role', 'supporter')
        ->using('App\Models\UserGroup');
    }
}
