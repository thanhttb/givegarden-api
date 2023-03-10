<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Group;
use Auth;
use Hash;
use Mail;
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
        $users = User::all();
        $result = [];
        foreach($users as $key => $user){
            $user->uid = $user->country_code."-".sprintf("%06d",$user->id);
            $groups = $user->groups()->select('groups.title', 'groups.open_at', 'groups.expired_at')->get()->toArray();
            // print_r($groups);
            $result[] = $user->toArray();
            $result[$key]['group_name'] = [];
            $result[$key]['group_date'] = [];
            foreach($groups as $g){
                $result[$key]['group_name'][] = $g['title'];
                $result[$key]['group_date'][] = date('d/m/Y', strtotime($g['open_at'])).'-'.date('d/m/Y', strtotime($g['expired_at']));
            }
            // $user->groups = $groups;
        }
        return response()->json([
            'data' => $result,
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

        $new_user = request(['email', 'name', 'phone', 'role', 'country_code']);
        $password = 123456;
        $new_user['password'] = Hash::make($password);
        $user = User::create($new_user);
        // $password = rand(100000,999999);
        $user->groups()->attach($request->groups);
        // $user->save();
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
    protected function sendOtp(Request $request){
        $request->validate([
            'email' => 'email|required',
            // 'password' => 'required'
        ]);
        $user = User::where('email', $request->email)->first();
       
        if($user->role != 'admin'){
            return response()->json(['data'=>'Only Admin Can Login', 402]);
        }else{
            $data = [];
            Mail::send('emails.mailEvent', $data ,function($message) {
                $message->from('noreply@givegarden.info', 'GiveGarden');
                $message->to('tranthanhsma@gmail.com', 'ThanhNT');
                $message->subject('Sendgrid Testing');
            });
            return response()->json('Mail Send Successfully');
        }
    }
    protected function checkCooldown(Request $request){
        $rules = ['email' => 'required', 'sent_at' => 'required'];
        $this->validate($request, $rules);

        $user = User::where('email', $request->email)->first();
        if ($user) {
            if ($user->sent_at) {
                $cooldown = strtotime($request->sent_at) - strtotime($user->sent_at);
                if ($cooldown < 200) {
                    return response()->json($cooldown);
                }
            }
        }
        return response()->json(['data' => 'User not exist', 402]);
    }

    protected function verifyEmail(Request $request){
        $rules = ['email' => 'required',
            'sent_at' => 'required'];
        $this->validate($request, $rules);

        //Verify Recapcha
        // $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
        //     'secret' => '6LcXZ2EaAAAAAJAWI_CwJP8O6rBdn7G3lCryhuOg',
        //     // 'secret' => '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe',
        //     'response' => $request->captcha,
        // ]);
        // if(! $response['success']) return response()->json('Mã Captcha không hợp lệ', 403);
        //Check phone number
        $user = User::where('email', $request->email)->first();
        if(!$user){
            return response()->json(['data'=>'Only Admin Can Login'], 402);
        }
        if($user->role != 'admin'){
            return response()->json(['data'=>'Only Admin Can Login'], 402);
        }else{
            $sent_at = strtotime($request->sent_at);
            //Check thời gian cooldown
            if( $user->sent_at ){
                if( $sent_at - strtotime($user->sent_at) < 200){
                    return response()->json(['data'=>'Mã OTP chưa hết hiệu lực'], 402);
                }
            }
            $otp = rand(1000,9999);
            
            $data = [$otp];
            Mail::send('emails.mailEvent', ['otp' => $otp] ,function($message) use($user) {
                $message->from('noreply@givegarden.info', 'GiveGarden');
                $message->to($user->email, $user->fullname);
                $message->subject('GiveGarden Login OTP');
            });
            $user->otp = $otp;
            $user->sent_at = date('Y-m-d h:i:s', $sent_at);
            $user->save();
            return response()->json(['Đã gửi mã otp, vui lòng kiểm tra Email'], 200);
            // try {
            //     Mail::send('emails.mailEvent', ['otp' => $otp] ,function($message) use($user) {
            //         $message->from('noreply@givegarden.info', 'GiveGarden');
            //         $message->to($user->email, $user->fullname);
            //         $message->subject('GiveGarden Login OTP');
            //     });
            //     $user->otp = $otp;
            //     $user->sent_at = date('Y-m-d h:i:s', $sent_at);
            //     $user->save();
            //     return response()->json('Đã gửi mã otp, vui lòng kiểm tra Email', 200);

            // } catch (\Throwable $th) {
            //     //throw $th;
            //     return response()->json('Mail Send Unsuccessfully');
            // }
            
            
        }
    }
    public function verifyOtp(Request $request){
        $rules = ['otp' => 'required', 'email'=>'required'];
        $this->validate($request, $rules);

        //OTP verified
        $user = User::where('email', $request->email)->first();

        if($user->otp == $request->otp){
            $user->otp = null;
            $user->sent_at = null;
            $user->save();
            Auth::loginUsingId($user->id);
            if($user->password){
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
            }
            else{
                return response()->json(['cp' => '203']);
            }
        }else{

            return response(['message' => 'Mã OTP không đúng vui lòng thử lại.']);
        }

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
        foreach($coaches as $user){
            $user->uid = $user->country_code."-".sprintf("%06d",$user->id);
        }
        return response()->json($coaches);
    }
    protected function getAvailableUser(){
        $users = User::where('role', 'user')->get();
        $result = [];
        $i = 0;
        foreach($users as $key => $u){
            if($u->groups()->count() == 0){
                $result[$i] = $u->toArray();
                $result[$i]['uid'] = $u->country_code."-".sprintf("%06d",$u->id);
                $i++;
            }
        }
        return response()->json($result);
    }
    protected function getSupporter(){
        $supporters = User::whereIn('role', ['supporter'])->get();
        foreach($supporters as $user){
            $user->uid = $user->country_code."-".sprintf("%06d",$user->id);
        }
        return response()->json($supporters);
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

    protected function updateProfile(Request $request){
        $this->validate($request, [
            'email' => 'required',
            
        ]);
        #Update new password
        $user = User::find(auth()->user()->id);
        $user->update([
            'name' => ($request->name)?$request->name:"John Doe",
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
                return response()->json('Mật khẩu mới không trùng khớp.');
            }
        }
        // if($request->avatar){
        //     echo "test";
        // }
        if($request->file('avatar')){
            // return response()->json('success');
            $files=$request->file('avatar');
            $image_path = $files->store('avatar', 'public');
            $user->avatar = 'https://api.givegarden.info/public/storage/'.$image_path;
            $user->save();
        }
    }
    protected function mail(){
        $data = [];
        Mail::send('emails.mailEvent', $data ,function($message) {
            $message->from('noreply@givegarden.info', 'GiveGarden');
            $message->to('tranthanhsma@gmail.com', 'ThanhNT');
            $message->subject('Sendgrid Testing');
        });
        return response()->json('Mail Send Successfully');
    }
}
