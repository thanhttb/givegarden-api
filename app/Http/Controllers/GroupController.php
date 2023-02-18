<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Group;
use App\Models\UserGroup;
class GroupController extends Controller
{
    //index
    protected function isAdmin(){
        $user = User::find(auth()->user()->id);
        if($user->role == 'admin'){
            return true;
        }else{
            return false;
        }
    }
    protected function get(){
        if(!$this->isAdmin()){
            return response()->json([
                'status_code' => 401,
                'message' => 'Unauthorized'
            ]);
        }
        $groups = Group::all();
        $result = [];
        foreach($groups as $key => $group){
            $result[$key] = $group;
            $result[$key]['users'] = $group->users()->get();
            $result[$key]['coaches'] = $group->coaches()->get();
        }
        return response()->json($result);
        
    }
    protected function create(Request $request){
        $this->validate($request, [
            'title' => 'required',
            'expired_at' => 'required',
            'coaches' => 'required',
        ]);

        $group['title'] = $request->title;
        $group['expired_at'] = date('Y-m-d', strtotime($request->expired_at));

        $group = Group::create($group);
        // $group->coaches()->syncWithoutDetach($request->coaches);
        $group->users()->sync(array_merge($request->users, $request->coaches));
        
        $result = $group;
        $result['coaches'] = $group->coaches;
        $result['users'] = $group->users;
        return response()->json($result);
    }

    protected function edit(Request $request){
        $this->validate($request, [
            'id' => 'required',
            'title' => 'required',
            'expired_at' => 'required',
            'coaches' => 'required',
        ]);

        $group = Group::find($request->id);
        if($group){
            $group->title = $request->title;
            $group->expired_at = date('Y-m-d', strtotime($request->expired_at));
            $group->save();
            $group->users()->sync(array_merge($request->users, $request->coaches));
            $result = $group;
            $result['coaches'] = $group->coaches;
            $result['users'] = $group->users;
            return response()->json($result);
        }
    }

}
