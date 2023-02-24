<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Group;
use Auth;
use Hash;
use Crypt;
class UserController extends Controller
{
    //
    protected function checkAuth(){
        
    }
    protected function isAdmin(User $user){
        if($user->role == 'admin'){
            return true;
        }else{
            return false;
        }
    }
    protected function index(){
        $user = User::find(auth()->user()->id);
        if(!$this->isAdmin($user)){
            return response()->json([
                'status_code' => 401,
                'message' => 'Unauthorized'
            ]);
        }
        return response()->json([
            'data' => User::all(),
        ]);
    }
    protected function create(Request $request){
        $user = User::find(auth()->user()->id);
        if(!$this->isAdmin($user)){
            return response()->json([
                'status_code' => 401,
                'message' => 'Unauthorized'
            ]);
        }

        $request->validate([
            'email' => 'email|required',
            'role' => 'required',
            'name' => 'required',
        ]);

        $new_user = request(['email', 'name', 'phone', 'role']);
        $password = 123456;
        $new_user['password'] = Hash::make($password);
        $user = User::create($new_user);
        // $password = rand(100000,999999);
        
        $user->save();
        return response()->json($user);

        //Send Email

        
    }
    protected function get(Request $request){
        if (Auth::check()) {
            $user = Auth::user();
            $group = $user->groups()->first();
            if($group){
                $user->group_id = $group->id;
            }else{
                $user->group_id = NULL;
            }
            return response()->json($user);
        } else {
            return false;
        }
        // $user = auth()->user()->id;
        // $user = User::find($user);
        // return response()->json($user);
    }

    protected function login(Request $request){
        try {
            $request->validate([
                'email' => 'email|required',
                'password' => 'required'
            ]);

            $credentials = request(['email', 'password']);
            
            
            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'status_code' => 500,
                    'message' => 'Unauthorized'
                ]);
            }

            $user = User::where('email', $request->email)->first();
            if(!$user->active){
                return response()->json([
                    'status_code' => 500,
                    'message' => 'User deactivated'
                ]);
            }
            if (!Hash::check($request->password, $user->password, [])) {
                throw new \Exception('Error in Login');
            }
            
            // $tokenResult = Crypt::encrypt(base64_encode($user->createToken('authToken')->plainTextToken));
            $tokenResult = $user->createToken('authToken')->plainTextToken;
            $group = $user->groups()->first();
            if($group){
                $user->group_id = $group->id;
            }else{
                $user->group_id = NULL;
            }
            return response()->json([
                'status_code' => 200,
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user,
            ]);
        } catch (\Exception $error) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Error in Login',
                'error' => $error,
            ]);
        }
    }
    
    protected function update(Request $request){
        $request->validate([
            // 'phone' => 'email|required',
            'id' => 'required',
        ]);
        $user = User::find(auth()->user()->id);
        if(!$this->isAdmin($user)){
            return response()->json([
                'status_code' => 401,
                'message' => 'Unauthorized'
            ]);
        }
        $user = User::find($request->id);
        $update = request(['email', 'name', 'role', 'phone']);
        if(!$user){
            return response()->json([
                'status_code' => 500,
                'message' => 'Unauthorized'
            ]); 
        }
        
        $user->update($update);
        return response()->json([
            'status_code' => 200,
            'user' => $user
        ]); 

    }
    protected function deactive(Request $request){
        $request->validate([
            // 'phone' => 'email|required',
            'id' => 'required',
        ]);
        $user = User::find(auth()->user()->id);
        if(!$this->isAdmin($user)){
            return response()->json([
                'status_code' => 401,
                'message' => 'Unauthorized'
            ]);
        }
        $user = User::find($request->id);
        if(!$user){
            return response()->json([
                'status_code' => 500,
                'message' => 'Unauthorized'
            ]); 
        }
        
        $user->active = 0;
        $user->save();

    }
    protected function reactive(Request $request){
        $request->validate([
            // 'phone' => 'email|required',
            'id' => 'required',
        ]);
        $user = User::find(auth()->user()->id);
        if(!$this->isAdmin($user)){
            return response()->json([
                'status_code' => 401,
                'message' => 'Unauthorized'
            ]);
        }
        $user = User::find($request->id);
        if(!$user){
            return response()->json([
                'status_code' => 500,
                'message' => 'Unauthorized'
            ]); 
        }
        
        $user->active = 1;
        $user->save();

    }
    protected function resetPassword(Request $request){
        $request->validate([
            // 'phone' => 'email|required',
            'id' => 'required',
        ]);
        $user = User::find(auth()->user()->id);
        if(!$this->isAdmin($user)){
            return response()->json([
                'status_code' => 401,
                'message' => 'Unauthorized'
            ]);
        }
        $user = User::find($request->id);
        if(!$user){
            return response()->json([
                'status_code' => 500,
                'message' => 'Unauthorized'
            ]); 
        }
        
        $user->password = Hash::make('123456');
        $user->save();

    }

    protected function getCoach(){
        $coaches = User::whereIn('role', ['admin', 'coach'])->get();
        return response()->json($coaches);
    }
    protected function getAvailableUser(){
        $users = User::where('role', 'user')->get();
        $result = [];
        foreach($users as $u){
            if($u->groups()->count() == 0){
                $result[] = $u;
            }
        }
        return response()->json($result);
    }
    //
    protected function createTestUser(){
        $input= [
            "name" => "Tran Thanh",
            "email" => "givegarden@gmail.com",
            "password" => Hash::make('123456'),
            "phone" => "0985951181",
            "role" => "admin",
            "active" => 1,
        ];
        User::create($input);
    }

    protected function updateUser(Request $request){
        $this->validate($request, [
            'email' => 'required',
            
        ]);
        #Update new password
        $user = User::find(auth()->user()->id)->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);
        #Update Password
        if($request->new_password){
            if($request->new_password_confirmation == $request->new_password){
                $user->password = Hash::make($request->new_password);
                $user->save;
            }
            else{
                return response()->json('Mật khẩu mới không trùng khớp.')
            }
        }
        if($files=$request->file('avatar')){
            $image_path = $files->store('avatar', 'public');
            $image = $image_path;
        }


    }
}
