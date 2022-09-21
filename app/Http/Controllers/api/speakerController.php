<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Course;
use App\Models\Module;
use App\Models\Speaker;
use DB;

class speakerController extends Controller
{

    public function addSpeaker(Request $request){
        $regex = "/^((?:https?\:\/\/|www\.)(?:[-a-z0-9]+\.)*[-a-z0-9]+.*)$/";
        
        $speaker = $request->validate([
            // 'moduleId' => 'required|numeric|min:1|exists:modules,id',
            'name' => 'required|string',
            // 'position' => 'string',
            // 'company' => 'string',
            // 'profile_path' => 'string', // 'regex:'.$regex,
            // 'company_path' => 'string', // 'regex:'.$regex,
            // 'role' => 'required|string',
            'speaker_image' => 'image|mimes:jpeg,png,jpg|max:2048',
            'company_image' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);
        // $role = 0;
        
        // ($request->role == "main")? $role = 1 : $role = 2;
        
        
        // dd($request->all(), $description);
        
        // check for duplicate Speaker
        $checker = DB::SELECT("SELECT * FROM speakers where name = '$request->name' and status <> 0");
        
        if(!empty($checker)){
            return response(["message" => "speaker already exists"], 409);
        }

        if(!empty($request->description)){
            $description = urlencode($request->description);
            $request->merge([
                'description' => $description,
            ]);

        }
        
        if(!empty($request->speaker_image) || !empty($request->speaker_link)){
            $profile_path = Speaker::speakerImage($request->all());
                
            $request->query->add(['profile_path' => $profile_path]);
        }
        
        if(!empty($request->company_image) || !empty($request->company_link)){
            $company_path = Speaker::speakerCompany($request->all());
                
            $request->query->add(['company_path' => $company_path]);
        }

        $speaker = Speaker::create($request->only('position', 'company', 'profile_path', 'company_path', 'description') +
                [
                    // 'moduleId' => $request->moduleId,
                    'name' => $request->name,
                    // 'role' => $role,
                ]);

        // $speaker = DB::SELECT("SELECT *, (CASE WHEN role = 1 THEN 'main' WHEN role = 2 THEN 'guest' END) role_code FROM speakers where id = $addSpeaker->id");
        // $speaker = DB::SELECT("SELECT * FROM speakers where id = $addSpeaker->id");
        
        $speaker["description"] = urldecode($speaker["description"]);
        // // dd($speaker);
        return response(["speaker" => $speaker], 200);

    }

    public function updateSpeaker($id, Request $request){
        
        $request->query->add(['id' => $id]);
        $regex = "/^((?:https?\:\/\/|www\.)(?:[-a-z0-9]+\.)*[-a-z0-9]+.*)$/";

        $speaker = $request->validate([
            'id' => 'required|numeric|min:1|exists:speakers,id',
            // 'moduleId' => 'numeric|min:1|exists:modules,id',
            'name' => 'string',
            // 'position' => 'string',
            // 'company' => 'string',
            // 'profile_path' => 'string', // 'regex:'.$regex,
            // 'company_path' => 'string', // 'regex:'.$regex,
            // 'role' => [
            //             'string',
            //             Rule::in(['main', 'guest']),
            //         ],
            'status' => [
                        'string',
                        Rule::in(['delete', 'active']),
                    ],
            'speaker_image' => 'image|mimes:jpeg,png,jpg|max:2048',
            'company_image' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);
        
        if($request->status == "delete"){
            $request['status'] = 0;
        }elseif($request->status == "active"){
            $request['status'] = 1;
        }

        $checker = DB::SELECT("SELECT * FROM speakers where id <> $id and name = '$request->name' and status <> 0");
        
        if(!empty($checker)){
            return response(["message" => "speaker already exists"], 409);
        }
        
        if(!empty($request->description)){
            $description = urlencode($request->description);
            $request->merge([
                'description' => $description,
            ]);

        }
        
        if(!empty($request->speaker_image) || !empty($request->speaker_link)){
            $path = Speaker::speakerImage($request->all(), $id);
        }
        if(!empty($request->company_image) || !empty($request->company_link)){
            $path = Speaker::speakerCompany($request->all(), $id);
        }
        
        $updateSpeaker = Speaker::find($id);
        
        $updateSpeaker->update($request->only('name', 'position', 'company', 'description', 'status') +
                        [ 'updated_at' => now()]
                        );

        $speaker = collect(\DB::SELECT("SELECT * FROM speakers where id = $id"))->first();
        // dd($speaker);
        $speaker->description = urldecode($speaker->description);

        return response(["message" => "successfully updated this speaker", "speaker" => $speaker], 200);
    }

    public function getSpeaker(Request $request, $id = 0){
        $request->query->add(['id' => $id]);
        $array = [];
    
        if($id > 0){
            $array['id'] = 'exists:speakers,id';
            $request->validate($array);  
            
            $speaker = COLLECT(\DB::SELECT("SELECT * FROM speakers s WHERE status <> 0 and s.id = $id"))->first();
            
            $speaker->description = urldecode($speaker->description);
        }else{
            $search_query = '';

            !empty($request->search)? $search_query = "and s.company like '%".addslashes($request->search)."%' or s.name like '%".addslashes($request->search)."%'" : '';

            $speaker = DB::SELECT("SELECT * FROM speakers s WHERE status <> 0 $search_query order by s.name asc");
            foreach ($speaker as $key => $value) {
                // dd($value->description);
                $value->description = urldecode($value->description);
            }
        }
        return response()->json(["speakers" => $speaker], 200);

    }
}
