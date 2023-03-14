<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Notification;
use App\Models\Group;
use App\Models\PostComment;
use App\Models\PostReaction;
use App\Models\User;
use App\Events\NewPost;
use App\Events\AllPost;
use App\Events\UpdatePostEvent;
use Illuminate\Support\Facades\Http;
use Auth;
class PostController extends Controller
{
    //
    protected function imageProcess($images){

    }
    protected function getAllPosts($group_id){
        $user_id = auth()->user()->id;

        $posts = Post::where('group_id', $group_id)->orderBy('created_at', 'DESC')->get();
        foreach($posts as &$p){
            // echo $p->id."-".$user."|";
            $check_reaction = PostReaction::where('post_id', $p->id)->where('user_id', $user_id)->first();
            // print_r($check_reaction);
            $p->liked = false;
            if($check_reaction) $p->liked = true;
            $comments = $p->comments;
            foreach($comments as &$c){
                $user = User::find($c->user_id);
                
                $c->user_fullname = $user->name;
                $c->user_avatar = $user->avatar;
                $c->user_level = $user->level;

            }
            $p->comments = $comments;
            $p->reactions = $p->reactions;
            $user = User::find($p->user_id);
            $p->user = $user;
        }
        return $posts;
    }
    protected function create(Request $request){
        $this->validate($request, [
            'image' => 'image|mimes:jpg,png,jpeg,gif,svg|max:4024',
            'content' => 'required',
            'type' => 'required',
            'is_public' => 'required',
            'group_id' => 'required',
        ]);
        $user = User::find(auth()->user()->id);
        // $group = $user->groups()->first();
        $group = Group::find($request->group_id);

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
        $images=array();
        for ($i=0; $i < $request->image_length; $i++) { 
            # code...
            $j = $i+1;
            if($files=$request->file('image_'.$j)){
                $image_path = $files->store('image', 'public');
                $images[]='https://api.givegarden.info/public/storage/'.$image_path;
            }
        }
        $post->images = $images;
        $post->save();

        $coaches = $group->coaches;
        foreach($coaches as $c){
            //Create Notification
            $noti['user_id'] = $c->id;
            $noti['event'] = 'created';
            $noti['post_id'] = $post->id;
            Notification::create($noti);
            //Notify Coach & Supporter
            $body = json_encode([
                "to" => $c->device_token,
                "title" => "GiveGarden",
                "body" => $user->name . " đã tạo một bài viết.",
                "channelId" => 'default',
                
            ]);
            try{
                $response = Http::withBody($body, 'application/json')->post('https://exp.host/--/api/v2/push/send');
            }
            catch (\Exception $exception) {
                return $exception->getMessage();
            }
            
            return response()->json($body);
            if($c->device_token){
                
                $response = Http::withBody($body, 'application/json')->post('https://exp.host/--/api/v2/push/send');
            }
            

        }
        

        //Return post
        // $comments = $post->comments()->orderBy('post_comments.created_at', 'DESC')->get();
        // foreach($comments as &$c){
        //     $user = User::find($c->user_id);
            
        //     $c->user_fullname = $user->name;
        //     $c->user_avatar = $user->avatar;
        //     $c->user_level = $user->level;
        //     $c->user_avatar = $user->avatar;

        // }
        // // $post->reactions = $post->reactions;
        // $post->comments = $comments->toArray();
        // $check_reaction = PostReaction::where('post_id', $post->id)->where('user_id', auth()->user()->id)->first();
        // $post->liked = false;
        // $post->reactions = $post->reactions;
        // if($check_reaction) $post->liked = true;
        // // print_r($post->toArray());

        // event(new NewPost($post->toArray()));
    }
    protected function getCommunity(Request $request){
        // $this->validate($request, ['group_id' => 'required']);
        $posts = $this->getAllPosts($request->group_id);
        // event(new AllPost($posts, $request->group_id));
        return response()->json($posts);
    }
    protected function createComment(Request $request){
        $this->validate($request, ['post_id' => 'required', 'content' => 'required']);
        $user = auth()->user()->id;
        
        $post = Post::find($request->post_id);
        $input['user_id'] = auth()->user()->id;
        $input['content'] = $request->content;
        $input['post_id'] = $request->post_id;
        $comment = PostComment::create($input);
        $comments = $post->comments()->orderBy('post_comments.created_at', 'DESC')->get();
        $check_reaction = PostReaction::where('post_id', $post->id)->where('user_id', $user)->first();
        $post->liked = false;
        if($check_reaction) $post->liked = true;
        foreach($comments as &$c){
            $user = User::find($c->user_id);
            
            $c->user_fullname = $user->name;
            $c->user_avatar = $user->avatar;
            $c->user_level = $user->level;

        }
        // print_r($comments->toArray());
        $post->reactions = $post->reactions()->get();
        $post->comments = $comments->toArray();
        $group_id = $post->group_id;
        $user = User::find($post->user_id);
        $post->user = $user;

        event(new UpdatePostEvent($post->toArray()));
    }
    protected function createReaction(Request $request){
        $this->validate($request, ['post_id' => 'required']);
        $user = auth()->user()->id;

        $post = Post::find($request->post_id);
        $input['user_id'] = auth()->user()->id;
        $input['post_id'] = $request->post_id;
        $check_exist = PostReaction::where('user_id', $input['user_id'])->where('post_id', $input['post_id'])->first();
        if($check_exist){
            $check_exist->forceDelete();
        }else{
            PostReaction::create($input);
        }
        $check_reaction = PostReaction::where('post_id', $post->id)->where('user_id', $user)->first();
        $post->liked = false;
        if($check_reaction) $post->liked = true;
        $comments = $post->comments()->orderBy('post_comments.created_at', 'DESC')->get();
        foreach($comments as &$c){
            $user = User::find($c->user_id);
            
            $c->user_fullname = $user->name;
            $c->user_avatar = $user->avatar;
            $c->user_level = $user->level;
            $c->user_avatar = $user->avatar;
        }
        $post->reactions = $post->reactions()->get()->toArray();
        $post->comments = $comments->toArray();
        $user = User::find($post->user_id);
        $post->user = $user;
        event(new UpdatePostEvent($post->toArray()));
    }
    protected function getPost($id){
        $post = Post::find($id);
        if($post){
            $comments = $post->comments()->orderBy('post_comments.created_at', 'DESC')->get();
            foreach($comments as &$c){
                $user = User::find($c->user_id);
                
                $c->user_fullname = $user->name;
                $c->user_avatar = $user->avatar;
                $c->user_level = $user->level;
                $c->user_avatar = $user->avatar;
            }
            $post->reactions = $post->reactions()->get()->toArray();
            $post->comments = $comments->toArray();
            $user = User::find($post->user_id);
            $post->user = $user;
            $check_reaction = PostReaction::where('post_id', $post->id)->where('user_id', auth()->user()->id)->first();
            $post->liked = false;
            if($check_reaction) $post->liked = true;
        }
        return response()->json($post);
    }
    
}
