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
            $j = $i+1;
            if($files=$request->file('image_'.$j)){
                $image_path = $files->store('image', 'public');
                $images[]=$image_path;
            }
        }
        $post->images = $images;
        $post->save();


        //Return post
        $comments = $post->comments()->get();
        foreach($comments as &$c){
            $user = User::find($c->user_id);
            
            $c->user_fullname = $user->fullname;
            $c->user_avatar = $user->avatar;
            $c->user_level = $user->level;

        }
        $post->post_reactions = $post->reactions;
        event(new NewPost($post, $comments, $post->reactions()->get()));
    }
    protected function getCommunity(Request $request){
        // $this->validate($request, ['group_id' => 'required']);

        $posts = Post::where('group_id', $request->group_id)->orderBy('created_at', 'DESC')->get();
        foreach($posts as &$p){
            $comments = $p->comments;
            foreach($comments as &$c){
                $user = User::find($c->user_id);
                
                $c->user_fullname = $user->fullname;
                $c->user_avatar = $user->avatar;
                $c->user_level = $user->level;

            }
            $p->comments = $comments;
            $p->reactions = $p->reactions;
            $user = User::find($p->user_id);
            $p->user = $user;
        }
        return response()->json($posts);
    }

    protected function createComment(Request $request){
        $this->validate($request, ['post_id' => 'required', 'content' => 'required']);
        
        $post = Post::find($request->post_id);
        $input['user_id'] = auth()->user()->id;
        $input['content'] = $request->content;
        $input['post_id'] = $request->post_id;
        $comment = PostComment::create($input);
        $comments = $post->comments()->get();
        foreach($comments as &$c){
            $user = User::find($c->user_id);
            
            $c->user_fullname = $user->fullname;
            $c->user_avatar = $user->avatar;
            $c->user_level = $user->level;

        }
        // print_r($comments->toArray());
        // $post->post_reactions = $post->reactions()->get();
        // return response()->json($post);
        event(new NewPost($post, $comments, $post->reactions()->get()));
    }

    protected function createReaction(Request $request){
        $this->validate($request, ['post_id' => 'required']);
        
        $post = Post::find($request->post_id);
        $input['user_id'] = auth()->user()->id;
        $input['post_id'] = $request->post_id;
        PostReaction::create($input);

        $comments = $post->comments()->get();
        foreach($comments as &$c){
            $user = User::find($c->user_id);
            
            $c->user_fullname = $user->fullname;
            $c->user_avatar = $user->avatar;
            $c->user_level = $user->level;

        }
        event(new NewPost($post, $comments, $post->reactions()->get()));
    }
}
