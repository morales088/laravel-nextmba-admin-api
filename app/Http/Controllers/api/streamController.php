<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use App\Models\Module;
use DB;

class streamController extends Controller
{
    public function verify(Request $request){
        $stream_link = env('STREAM_LINK');
        $stream_api_key = env('STREAM_API_TOKEN');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$stream_api_key,
        ])->get($stream_link.'/user/tokens/verify', [
            
        ]);

        dd($response->json(), $response->ok(), $response->successful(), $response->failed());

    }

    public function live(Request $request){
        $stream_link = env('STREAM_LINK');
        $stream_api_key = env('STREAM_API_TOKEN');
        $stream_account_id = env('STREAM_ACCCOUNT_ID');
        // $currentIp = request()->ip();
        // dd($currentIp);

        
        $stream = $request->validate([
            'module_id' => 'required|numeric|min:1|exists:modules,id',
            'stream_name' => 'required|string',
        ]);

        // check module's uid
        $module = Module::find($request->module_id);

        // dd($module, $module->broadcast_status);

        if($module->broadcast_status == 1){ 

            $response = Http::acceptJson()->withHeaders([
                'Authorization' => "Bearer $stream_api_key",
            ])->post($stream_link."/accounts/$stream_account_id/stream/live_inputs", [
                "meta" => [
                    "name" => $request->stream_name
                ],
                "recording" => [
                    "mode" => "automatic"
                ],
            ]);

            // dd($response->json(), $response->ok(), $response->successful(), $response->failed());
            // dd($response->serverError(), $response->clientError(), $response->failed());
            
            if($response->serverError() || $response->clientError() || $response->failed()){
                return response(["message" => "Unable to create live stream",], 500);
            }

            $cf_response_result = $response->json()['result'];
            $uid = $cf_response_result['uid'];
            $steam_info = $cf_response_result['rtmpsPlayback'];
            // dd($cf_response_result);

            // save uid/rtmps_url/streamKey/stream_json
            DB::table('modules')
            ->where('id', $request->module_id)
            ->update(
              [
                'live_url' => $uid, // ui
                'steam_info' => $steam_info,
                'stream_json' => $cf_response_result,
                'updated_at' => now(),
              ]
            );


            // dd($response->json(), $response->ok(), $response->successful(), $response->failed(), $response->body(), $response->headers() );

            return response()->json(["cloudflare_live_result" => $cf_response_result], 200);

        }else {
            return response(["message" => "live stream not supported for this module.",], 422);
        }


    }

    public function watch(Request $request){
        $stream_link = env('STREAM_LINK');
        $stream_api_key = env('STREAM_API_TOKEN');
        $stream_account_id = env('STREAM_ACCCOUNT_ID');
        // $currentIp = request()->ip();
        // dd($currentIp);

        
        $stream = $request->validate([
            'uid' => 'required|string',
        ]);
        
        $response = Http::acceptJson()->withHeaders([
            'Authorization' => "Bearer $stream_api_key",
        ])->get($stream_link."/accounts/$stream_account_id/stream/live_inputs/$request->uid/videos", [
            
        ]);

        // dd($response->json(), $response->ok(), $response->successful(), $response->failed());
        // dd($response->serverError(), $response->clientError(), $response->failed());

        $cf_response_result = $response->json()['result'];

        return response()->json(["cloudflare_watch_result" => $cf_response_result], 200);
    }

    public function delete(Request $request){
        $stream_link = env('STREAM_LINK');
        $stream_api_key = env('STREAM_API_TOKEN');
        $stream_account_id = env('STREAM_ACCCOUNT_ID');
        // $currentIp = request()->ip();
        // dd($currentIp);

        
        $stream = $request->validate([
            'stream_obs_id' => 'required|string',
        ]);
        
        $response = Http::acceptJson()->withHeaders([
            'Authorization' => "Bearer $stream_api_key",
        ])->delete($stream_link."/accounts/$stream_account_id/stream/live_inputs/$request->stream_obs_id", [
            
        ]);

        // dd($response->json(), $response->ok(), $response->successful(), $response->failed());
        // dd($response->serverError(), $response->clientError(), $response->failed());

        
        if($response->serverError() || $response->clientError() || $response->failed()){
            
            return response(["message" => "Unable to delete live stream / live stream not found",], 500);

        }else{

            $cf_response_result = $response->json()['result'];
        
            return response()->json(["cloudflare_delete_result" => $cf_response_result], 200);
        }

    }
}
