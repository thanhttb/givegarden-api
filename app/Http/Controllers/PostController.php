<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostReaction;
use App\Models\User;
use App\Events\NewPost;
use Auth;
class PostController extends Controller
{
    //
    protected function create(Request $request){
        $this->validate($request, [
            'image' => 'image|mimes:jpg,png,jpeg,gif,svg|max:4024',
            'content' => 'required',
            'type' => 'required',

        ]);
        $user = User::find(auth()->user()->id);
        $group = $user->groups()->first();

        $input['content'] = $request->content;
        $input['user_id'] = $user->id;
        $input['type'] = $request->type;
        if($request->is_public){
            $input['group_id'] = ($group) ? $group->id : NULL;
        }
        if($user->role == 'user'){
            $input['status'] = 'pending';
        }else{
            $input['status'] = 'approved';
        }
        $post = Post::create($input);
        //Upload Post
        //Upload Post
        $images=array();
        for ($i=0; $i < $request->image_length; $i++) { 
            # code...
            if($files=$request->file('image_'.$i)){
                $image_path = $files->store('image', 'public');
                $images[]=$image_path;
            }
        }
        $post->images = $images;
        $post->save();

        event(new NewPost($post));
    }
    protected function getCommunity(Request $request){
        $this->validate($request, ['group_id' => 'required']);

        $posts = Post::where('group_id', $request->group_id)->orderBy('created_at', 'DESC')->get();
        foreach($posts as &$p){
            $p->comments = $p->comments;
            $p->reactions = $p->reactions;
            $user = User::find($p->user_id);
            $p->user = $user;
        }
        return response()->json($posts);
    }
}
