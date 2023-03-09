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
    protected function index(Request $request){
        $this->validate($request, ['id' => 'required']);

        $group = Group::find($request->id);
        if($group){
            $user = $group->users()->orderBy('level', 'DESC')->limit(3)->get();
            $group->top_user = $user;
        }
        return response()->json($group);
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
            // 'coaches' => 'required',
        ]);

        $group['title'] = $request->title;
        $group['expired_at'] = date('Y-m-d', strtotime($request->expired_at));
        $group['open_at'] = date('Y-m-d', strtotime($request->open_at));
        $group = Group::create($group);
        // $group->coaches()->syncWithoutDetach($request->coaches);
        // $group->users()->sync(array_merge($request->users, $request->coaches));
        
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
            // 'coaches' => 'required',
        ]);

        $group = Group::find($request->id);
        if($group){
            $group->title = $request->title;
            $group->expired_at = date('Y-m-d', strtotime($request->expired_at));
            $group->open_at = date('Y-m-d', strtotime($request->open_at));
            $group->save();
            // $group->users()->sync(array_merge($request->users, $request->coaches));
            $result = $group;
            // $result['coaches'] = $group->coaches;
            // $result['users'] = $group->users;
            return response()->json($result);
        }
    }

    protected function detail($id){
        
        $group = Group::find($id);
        $result = [];
        if($group){
            $result = $group->toArray();
            $result['count_user'] = 0;
            $result['count_coach'] = 0;
            $result['count_supporter'] = 0;
            $coaches = $group->coaches;
            $users = $group->users;

            // print_r($coaches->toArray());
            foreach($users as $u){
                $result['count_user']++;
                $u->uid = $u->country_code."-".sprintf("%06d",$u->id);
                $result['users'][] = $u->toArray();
            }
            foreach($coaches as $u){
                switch ($u->role) {
                    case 'coach':
                        # code...
                        $result['count_coach']++;
                        break;
                    case 'admin':
                        # code...
                        $result['count_coach']++;
                        break;
                    case 'supporter':
                        # code...
                        $result['count_supporter']++;
                        break;
                    default:
                        # code...
                        break;
                }
                $u->uid = $u->country_code."-".sprintf("%06d",$u->id);
                $result['users'][] = $u->toArray();
            }

        }
        return response()->json($result);
    }
    protected function removeUser(Request $request){
        $this->validate($request, [
            'group_id' => 'required',
            'user_id' => 'required',
        ]);
        $ug = UserGroup::where('group_id', $request->group_id)->where('user_id', $request->user_id)->first();
        if($ug){
            $ug->forceDelete();
        }else{
            return response()->json('Không tìm thấy user', 201);
        }
        return response()->json('Thành công', 200);
    }
    // protected function assignUser
    protected function assignUser(Request $request){
        $this->validate($request, [
            'id' => 'required',
        ]);

        $group = Group::find($request->id);
        if($group){
            $users = UserGroup::where('group_id', $request->id)->select('user_id as id')->get();
            // $users = $group->allUser()->select('users.id')->get();
            $users = array_column($users->toArray(), 'id');
            $u = array_unique((array_merge($request->users, $request->coaches, $request->supporters, $users)));
            print_r($u);
            $group->users()->sync($u);
            $result = $group;
            return response()->json($result);
        }
    }
}
