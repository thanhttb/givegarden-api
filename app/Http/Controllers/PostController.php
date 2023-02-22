<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostReaction;
use App\Models\User;
use Auth;
class PostController extends Controller
{
    //
    protected function create(Request $request){
        $this->validate($request, [
            'image' => 'image|mimes:jpg,png,jpeg,gif,svg|max:4024',
        ]);
        $user = User::find(auth()->user()->id);
        $group = $user->groups()->first();

        $input['content'] = $request->content;
        $input['user_id'] = $user->id;
        $input['type'] = $request->type;
        if($request->is_public){
            $input['group_id'] = $group->id;
        }
        if($user->role == 'user'){
            $input['status'] = 'pending';
        }else{
            $input['status'] = 'approved';
        }
        $post = Post::create($input);
        //Upload Post
        $images=array();
        if($files=$request->file('images')){
            foreach($files as $file){
                $image_path = $file->store('image', 'public');
                $images[]=$image_path;
            }
        }
        $post->images = $images;
        $post->save();
    }
}
