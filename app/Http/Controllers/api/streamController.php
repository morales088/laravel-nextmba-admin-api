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
            $stream_info = $cf_response_result['rtmps'];
            $srt = $cf_response_result['srt'];
            $srt_url = $srt['url']."?passphrase=".$srt['passphrase']."&streamid=".$srt['streamId'];
            // dd($cf_response_result, $stream_info);

            // save uid/rtmps_url/streamKey/stream_json
            DB::table('modules')
            ->where('id', $request->module_id)
            ->update(
              [
                'uid' => $uid, // ui
                'stream_info' => $stream_info,
                'stream_json' => $cf_response_result,
                'srt_url' => $srt_url,
                'broadcast_status' => 0,
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
            'module_id' => 'required|numeric|min:1|exists:modules,id',
            'stream_obs_id' => 'required|string',
        ]);
        
        $response = Http::acceptJson()->withHeaders([
            'Authorization' => "Bearer $stream_api_key",
        ])->delete($stream_link."/accounts/$stream_account_id/stream/live_inputs/$request->stream_obs_id", [
            
        ]);

        // dd($response->json(), $response->ok(), $response->successful(), $response->failed());
        // dd($response->serverError(), $response->clientError(), $response->failed());

            
        // remove stream_json
        DB::table('modules')
        ->where('id', $request->module_id)
        ->update(
            [
            // 'uid' => null,
            'stream_info' => null,
            'updated_at' => now(),
            ]
        );
        
        if($response->serverError() || $response->clientError() || $response->failed()){

            return response(["message" => "Unable to delete live stream / live stream not found",], 500);

        }else{


            $cf_response_result = $response->json()['result'];
        
            return response()->json(["cloudflare_delete_result" => $cf_response_result], 200);
        }

    }

    public function watchReplay(Request $request){
        $stream_link = env('STREAM_LINK');
        $stream_api_key = env('STREAM_API_TOKEN');
        $stream_account_id = env('STREAM_ACCCOUNT_ID');

        $stream = $request->validate([
            'module_id' => 'required|numeric|min:1|exists:modules,id',
            'uid' => 'required|string',
        ]);


        
        $response = Http::acceptJson()->withHeaders([
            'Authorization' => "Bearer $stream_api_key",
        ])->get($stream_link."/accounts/$stream_account_id/stream/$request->uid", [
            
        ]);

        $cf_response_result = $response->json()['result'];

        // $a = "https://customer-2u5nuvressjoja58.cloudflarestream.com/7267e2f691aea1cf1c6badf2295d7f29/manifest/video.m3u8";

        // $domain = parse_url($a);
        // dd($domain);
        $time = now()->addHours(3);
        // dd( $time, strtotime($time) );

        $response_token = Http::acceptJson()->withHeaders([
            'Authorization' => "Bearer $stream_api_key",
        ])->post($stream_link."/accounts/$stream_account_id/stream/$request->uid/token", [
            'exp' => strtotime($time),
        ]);
        
        $token_response = $response_token->json()['result'];
        // dd($token_response['token']);

        return response()->json(["access_token" => $token_response['token'], "cloudflare_replay_result" => $cf_response_result], 200);

    }
}
