<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use DB;

class loginController extends Controller
{
    public function index(Request $request){
        // $request->user()->token()->id  // token id
        return User::all();
    }

    public function loginTest (Request $request){
        if(Auth::attempt($request->only('email','password'))){
            $user = Auth::user();

            $accessToken = Auth::user()->createToken('authToken')->accessToken;

            return response(["token" => $accessToken], 200);
        }

        return response(["message" => "Invalid login credentials"], 401);
    }

    public function login(Request $request){
        $currentIp = request()->ip();

        $login = $request->validate([
            'email' => 'required|string',
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



    public function register(Request $request){

        // creating client 

        // $oauth_client=new \App\oAuthClient();
        // $oauth_client->user_id=$user->id;
        // $oauth_client->id=$email;
        // $oauth_client->name=$user->name;
        // $oauth_client->secret=base64_encode(hash_hmac('sha256',$password, 'secret', true));
        // $oauth_client->password_client=1;
        // $oauth_client->personal_access_client=0;
        // $oauth_client->redirect='';
        // $oauth_client->revoked=0;
        // $oauth_client->save();


    }


}
