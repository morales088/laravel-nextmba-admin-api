<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use DB;

class loginController extends Controller
{
    public function index(Request $request){
        // $request->user()->token()->id  // token id
        return User::all();
    }

    public function personalAccessLogin (Request $request){

        $login = $request->validate([
            'email' => 'required|string|exists:users,email,status,1',
            'password' => 'required|string'
        ]);

        if(!Auth::attempt($login)){
            return response(["message" => "Invalid login credentials"], 401);
        }

        $accessToken = Auth::user()->createToken('authToken')->accessToken;

        return response()->json(["user" => Auth::user(), "access_token" => $accessToken], 200);
        
    }

    public function login(Request $request){
        $currentIp = request()->ip();

        $login = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        if(!Auth::attempt($login)){
            return response(["message" => "Invalid login credentials"], 401);
        }

        // $accessToken = Auth::user()->createToken('authToken')->accessToken;

        // return response(["user" => Auth::user(), "access_token" => $accessToken], 200);
        
        // return response()->json(["user" => Auth::user(), "access_token" => $accessToken], 200);
        
        
        // get client id
        $user = collect(\DB::SELECT("SELECT u.*, ou.id as client_id, ou.secret as client_secret, ou.redirect
                            FROM 
                            users as u 
                            LEFT JOIN oauth_clients as ou ON u.id = ou.user_id
                            WHERE u.id = ".Auth::user()->id." AND ou.password_client = 1"))->first();

        
        // dd(!$user);
        // return error if user dont have client id & secret
        if(!$user){
            return response()->json(["message" => "this user don't have client"], 400);
        }


        // post to /oauth/token
            // 2.1 parameters - grant_type (ex: password), client_id (int), client_secret, username, password, scope
            // 2.2 return token_type, expires_in (172800), access_token, refresh_token

        $response = Http::post($currentIp.'/oauth/token', [
                'grant_type' => 'password',
                'client_id' => $user->client_id,
                'client_secret' =>  $user->client_secret,
                'username' => $request->email,
                'password' => $request->password,
                'scope' => '*',
        ]);

        
        // throw exception on post error
        // dd($response->throw());
        if($response->serverError()){
            return response()->json(["message" => "Internal Server Error"], 500);
        }
        // dd($response->json(), $response->ok(), $response->successful(), $response->failed());


        return response()->json(["user" => Auth::user(), "token" => $response->json()], 200);
    }



    public function register(Request $request) {
 
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|unique:users',
            'password' => 'required|confirmed|min:8',
            'role' => 'integer|in:1,2',
        ]);

        $password = Hash::make($request->password);
        $admin_type = $request->role == 1 ? 1 : 2; // set admin type based on role

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $password,
            'role'=> $request->role,
            'type' => $admin_type
        ]);
        
        return response(["admin" => $user], 200);

    }

    public function admin(Request $request, $id = 0) {
                
        $request->query->add(['id' => $id]);
        
        if ($id > 0) {
            $admin = User::where('id', $id)->get();
        } else {
            $admin = User::all();
        }

        foreach ($admin as $user) {
            $user->status = ($user->status == 1) ? 'active' : 'deleted';
        }
    
        return response()->json(["admin" => $admin], 200);
    }

    public function updateAdmin(Request $request, $id) {

        $request->query->add(['id' => $id]);

        $request->validate([
            'id' => 'required|exists:users,id',
            'email' => 'unique:users',
            'role' => 'in:1,2',
            'type' => 'in:1,2',
        ]);

        $user = User::findOrFail($id);
        
        $data = $request->only('name', 'email', 'role', 'type');

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data + ['updated_at' => now()]);

        return response()->json(["admin" => $user], 200);               

    }

}
