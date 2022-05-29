<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Course;
use App\Models\Module;
use App\Models\Speaker;
use App\Models\Topic;
use App\Models\Speakerrole;
use DB;

class courseController extends Controller
{
    public function index(Request $request){
        $courses = DB::SELECT("select c.id course_id, c.name, c.description, count(s.id) total_students, s.status, (CASE WHEN s.status = 0 THEN 'deleted' WHEN s.status = 1 THEN 'active' END) as status_code, c.created_at, c.updated_at
                                from students s
                                left join studentcourses sc ON sc.studentId = s.id
                                left join courses c ON c.id = sc.courseId
                                where s.status <> 0 and sc.status <> 0 and c.status <> 0
                                group by c.id");

        // $courses = DB::SELECT("select *, (CASE WHEN m.status = 0 THEN 'deleted' WHEN m.status = 1 THEN 'active' END) as status_code, concat(m.date, '', m.starting_time) start_date
        // from modules m");

        return response()->json(["courses" => $courses], 200);
        
    }

    public function addModule(Request $request){
        $regex = "/[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)?/gi";
        
        $module = $request->validate([
            'courseId' => 'numeric|min:1|exists:courses,id',
            'name' => 'string',
            'description' => 'string',
            'chat_url' => 'regex:'.$regex,
            'live_url' => 'regex:'.$regex,
            'topic' => 'string',
            'calendar_link' => 'regex:'.$regex,
            // 'date' => 'date_format:Y-m-d',
            'start_date' => 'date_format:Y-m-d H:i:s',
            'end_date' => 'date_format:Y-m-d H:i:s'
        ]);
        

        $checker = DB::SELECT("SELECT * FROM modules where courseId = $request->courseId and start_date = '$request->start_date' and status <> 0");

        if(!empty($checker)){
            return response(["message" => "record already exist. please double check the course id and date"], 409);
        }

        $module = Module::create($request->only('topic', 'chat_url', 'live_url', 'calendar_link') +
            [
                'courseId' => $request->courseId,
                'name' => $request->name,
                'description' => $request->description,
                // 'date' => $request->date,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);
        
        return response(["message" => "successfully added module's course", "module" => $module], 200);
        
    }

    public function updateModule(Request $request,$id){
        $regex = "/[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)?/gi";
        $request->query->add(['id' => $id]);

        $request->validate([
            'id' => 'required|numeric|min:1|exists:modules,id',
            'courseId' => 'numeric|min:1|exists:courses,id',
            'name' => 'string',
            'description' => 'string',
            'chat_url' => 'regex:'.$regex,
            'live_url' => 'regex:'.$regex,
            'topicId' => 'numeric|min:1|exists:topics,id',
            'calendar_link' => 'regex:'.$regex,
            // 'date' => 'date_format:Y-m-d',
            'start_date' => 'date_format:Y-m-d H:i:s',
            'end_date' => 'date_format:Y-m-d H:i:s',
            'module_status' => [
                        'string',
                        Rule::in(['draft', 'published', 'archived']),
                    ],
            'broadcast_status' => [
                        'string',
                        Rule::in(['upcoming', 'live', 'pending_replay', 'replay']),
                    ],
        ]);
        
        if($request->module_status == "draft"){
            $request['status'] = 1;
        }elseif($request->module_status == "published"){
            $request['status'] = 2;
        }elseif($request->module_status == "archived"){
            $request['status'] = 3;
        }

        if($request->broadcast_status == "upcoming"){
            $request['broadcast_status'] = 1;
        }elseif($request->broadcast_status == "live"){
            $request['broadcast_status'] = 2;
        }elseif($request->broadcast_status == "pending_replay"){
            $request['broadcast_status'] = 3;
        }elseif($request->broadcast_status == "replay"){
            $request['broadcast_status'] = 4;
        }
        
        // dd($request->all());
        $module = Module::find($id);
        // dd($request->all());
        // $module->update($request->only('courseId', 'name', 'description', 'date', 'starting_time', 'end_time', 'topic', 'broadcast_status', 'status') +
        //                 [ 'updated_at' => now()]
        //                 );
        
        $module->update($request->only('courseId', 'name', 'description', 'chat_url', 'live_url', 'topicId', 
                                        'calendar_link', 'start_date', 'end_date', 'broadcast_status', 'status') +
                        [ 'updated_at' => now()]
                        );
                        
        $getmodule = COLLECT(\DB::SELECT("select m.*, 
                                        (CASE WHEN m.status = 1 THEN 'draft' WHEN m.status = 2 THEN 'published' WHEN m.status = 3 THEN 'archived' END) module_status,
                                        (CASE WHEN m.status = 1 THEN 'upcoming' WHEN m.status = 2 THEN 'live' WHEN m.status = 3 THEN 'pending_replay' WHEN m.broadcast_status = 4 THEN 'replay' END) broadcast_status,
                                        t.name topic_name
                                        from modules m 
                                        left join topics t ON t.id = m.topicId
                                        where m.id = $module->id and m.status <> 0 or t.status <> 0"))->first();

        return response(["message" => "successfully updated this module", "module" => $getmodule], 200);

        
    }

    public function getModule($id, Request $request){

        $request->query->add(['id' => $id]);

        $module = $request->validate([
            'id' => 'required|numeric|min:1|exists:modules,id'
        ]);
        
        // $module = Module::where('id', $request->id)->selectRaw("*, (CASE WHEN status = 0 THEN 'deleted' WHEN status = 1 THEN 'active' END) status_code")->first();

        // $module->speakers = DB::SELECT("select *, (CASE WHEN role = 1 THEN 'main' WHEN role = 2 THEN 'guest' END) role_code
        //                                         , (CASE WHEN status = 0 THEN 'deleted' WHEN status = 1 THEN 'active' END) status_code
        //                                 from speakers where moduleId = $request->id and status <> 0");
        $module = COLLECT(\DB::SELECT("select m.*, 
                                        (CASE WHEN m.status = 1 THEN 'draft' WHEN m.status = 2 THEN 'published' WHEN m.status = 3 THEN 'archived' END) module_status,
                                        (CASE WHEN m.status = 1 THEN 'upcoming' WHEN m.status = 2 THEN 'live' WHEN m.status = 3 THEN 'pending_replay' WHEN m.broadcast_status = 4 THEN 'replay' END) broadcast_status,
                                        t.name topic_name
                                        from modules m 
                                        left join topics t ON t.id = m.topicId
                                        where m.id = $id and m.status <> 0 or t.status <> 0"))->first();
                                        
        $topics = DB::SELECT("SELECT t.*, s.name speaker_name, s.position speaker_position, s.company speaker_company, s.profile_path speaker_profile_path, s.company_path speaker_company_path,
                                    (CASE WHEN t.status = 0 THEN 'deleted' WHEN t.status = 1 THEN 'active' END) as status_code
                                    FROM topics t
                                    LEFT JOIN speakers s ON s.id = t.speakerId
                                    where t.status <> 0 and s.status <> 0 and t.moduleID = $module->id");
        $module->topics = $topics;
        // dd($module);
        return response(["module" => $module], 200);

    }

    
    public function getModules($id, Request $request){
        $request->query->add(['id' => $id]);

        $course = $request->validate([
            'id' => 'numeric|min:1|exists:courses,id',
        ]);
        
        $course = Course::where('id', $request->id)->where('status', '<>', 0)->first();
        
        // // $modules = Module::where('courseId', $request->id)->where('status', '<>', 0)->get();
        // $modules = DB::SELECT("SELECT *, (CASE WHEN status = 0 THEN 'deleted' WHEN status = 1 THEN 'active' END) status_code FROM modules WHERE courseId = $request->id and status <> 0");

        // foreach ($modules as $key => $value) {
            
        //     // $value->speakers = Speaker::where('moduleId', $value->id)->where('status', '<>', 0)->get();
        //     $value->speakers = DB::SELECT("select *, (CASE WHEN role = 1 THEN 'main' WHEN role = 2 THEN 'guest' END) role_code from speakers where moduleId = $value->id and status <> 0");;
        // }

        $modules = DB::SELECT("select m.*, 
                                (CASE WHEN m.status = 1 THEN 'draft' WHEN m.status = 2 THEN 'published' WHEN m.status = 3 THEN 'archived' END) broadcast_status,
                                (CASE WHEN m.status = 1 THEN 'upcoming' WHEN m.status = 2 THEN 'live' WHEN m.status = 3 THEN 'pending_replay' WHEN m.broadcast_status = 4 THEN 'replay' END) broadcast_status,
                                t.name topic_name
                                from modules m 
                                left join topics t ON t.id = m.topicId
                                where m.courseId = $id and m.status <> 0 or t.status <> 0");

        foreach ($modules as $key => $value) {
            
            $value->topics = DB::SELECT("SELECT t.*, s.name speaker_name, s.position speaker_position, s.company speaker_company, s.profile_path speaker_profile_path, s.company_path speaker_company_path,
                                            (CASE WHEN t.status = 0 THEN 'deleted' WHEN t.status = 1 THEN 'active' END) as status_code
                                            FROM topics t
                                            LEFT JOIN speakers s ON s.id = t.speakerId
                                            where t.status <> 0 and s.status <> 0 and t.moduleID = $value->id");
        }
        
        return response()->json(["course" => $course, "modules" => $modules], 200);

    }
    
    // public function liveModule(Request $request){
    //     $request->validate([
    //         'module_id' => 'required|numeric|min:1|exists:modules,id',
    //         // 'status' => 'required|string',
    //         'status' => [
    //                         'required',
    //                         Rule::in(['live', 'not_live']),
    //                     ],
    //     ]);

    //     if($request->status == "live"){
    //         $status = 1;
    //         $message = "live";
    //     }elseif($request->status == "not_live"){
    //         $status = 0;
    //         $message = "offline";
    //     }
        
    //     $liveModule = Module::find($request->module_id);
        
    //     $liveModule->update(
    //                     [ 
    //                         'is_live' => $status,
    //                         'updated_at' => now(),
    //                     ]
    //                     );

    //     return response(["message" => "module id $request->module_id is now $message",], 200);
    // }

    public function addTopic(Request $request){
        $regex = "/[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)?/gi";
        
        $speaker = $request->validate([
            'module_id' => 'required|numeric|min:1|exists:modules,id',
            'speaker_id' => 'required|numeric|min:1|exists:speakers,id',
            'name' => 'required|string',
            'video_link' => 'regex:'.$regex,
            'description' => 'string',
            'speaker_role' => [
                        'required',
                        Rule::in(['main', 'guest']),
                    ],
        ]);

        $role = 0;
            
        ($request->speaker_role == "main")? $role = 1 : $role = 2;

        // check duplicate topic, speaker
        $checker = DB::SELECT("select *
                                from topics t
                                left join speaker_roles sr on t.id = sr.topicId
                                where t.moduleId = $request->module_id and t.speakerId = $request->speaker_id and t.speakerId = $request->speaker_id and t.status <> 0");
                                
        if(!empty($checker) || !empty($checker) && $role == 1){
            return response(["message" => "main speaker / speaker already exists"], 409);
        }

        $DBtransaction = DB::transaction(function() use ($request, $role) {

            // insert to topics table

            $addTopic = Topic::create($request->only('video_link', 'description') +
                [
                    'moduleId' => $request->module_id,
                    'speakerId' => $request->speaker_id,
                    'name' => $request->name,
                ]);


            // insert to speakers_topic table
            $addSpeakertopic = Speakerrole::create(
                [
                    'topicId' => $addTopic->id,
                    'speakerId' => $request->speaker_id,
                    'role' => $role,
                ]);

                $topic = collect(\DB::SELECT("select t.*, s.id speaker_id, s.name speaker_name, s.position speaker_position, s.company speaker_company, s.profile_path speaker_profile_path, s.company_path speaker_company_path, 
                            (CASE WHEN role = 0 THEN 'delete' WHEN role = 1 THEN 'active' END) topic_status
                            from topics t
                            left join speaker_roles sr on t.id = sr.topicId
                            left join speakers s ON s.id = t.speakerId
                            where t.moduleId = $request->module_id and t.speakerId = $request->speaker_id and sr.role = 1 and t.status <> 0 and s.status <> 0"))->first();

                return $topic;
            
        });

        return response(["topic" => $DBtransaction], 200);

    }

    public function updateTopic($id, Request $request){
        
        $request->query->add(['id' => $id]);
        $regex = "/[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)?/gi";
        
        $speaker = $request->validate([
            'id' => 'required|numeric|min:1|exists:topics,id',
            'module_id' => 'required|numeric|min:1|exists:modules,id',
            'speaker_id' => 'required|numeric|min:1|exists:speakers,id',
            'name' => 'string',
            'video_link' => 'regex:'.$regex,
            'description' => 'string',
            'speaker_role' => [
                        'string',
                        Rule::in(['main', 'guest']),
                    ],
            'status' => [
                        'string',
                        Rule::in(['delete', 'active']),
                    ],
        ]);
        
        
        if($request->status == "delete"){
            $request['status'] = 0;
        }elseif($request->status == "active"){
            $request['status'] = 1;
        }

        $role = 0;
            
        ($request->speaker_role == "main")? $role = 1 : $role = 2;

        // check duplicate main speaker
        $checker = DB::SELECT("select *
                                from topics t
                                left join speaker_roles sr on t.id = sr.topicId
                                where t.moduleId = $request->module_id and t.speakerId = $request->speaker_id and sr.role = 1 and t.status <> 0");

        if(!empty($checker) && $role == 1){
            return response(["message" => "speaker already exists"], 409);
        }

        // dd($request->all());

        $DBtransaction = DB::transaction(function() use ($request, $role) {
        
            $updateTopic = Topic::find($request->id);
            
            // dd($updateTopic);
        
            $updateTopic->update($request->only('module_id', 'name', 'video_link', 'description', 'status') +
                            [ 'updated_at' => now()]
                            );
                            

            // update speker_role
            if(!empty($request->speaker_role)){

                DB::table('speaker_roles')
                ->where('topicId', $request->id)
                ->where('speakerId', $request->speaker_id)
                ->update(['role' => $role]);
                
            }
            $topic = collect(\DB::SELECT("select t.*, s.id speaker_id, s.name speaker_name, s.position speaker_position, s.company speaker_company, s.profile_path speaker_profile_path, s.company_path speaker_company_path, 
                            (CASE WHEN role = 0 THEN 'delete' WHEN role = 1 THEN 'active' END) topic_status
                            from topics t
                            left join speaker_roles sr on t.id = sr.topicId
                            left join speakers s ON s.id = t.speakerId
                            where t.moduleId = $request->module_id and t.speakerId = $request->speaker_id and sr.role = 1 and t.status <> 0 and s.status <> 0"))->first();

            return $topic;
        });

        return response(["topic" => $DBtransaction], 200);

    }

    public function getTopic(Request $request,$module_id, $id = 0){
        
        $request->query->add(['id' => $id]);
        $request->query->add(['moduleId' => $module_id]);

        $array = [
                'moduleId' => 'required|exists:modules,id',
                ];

        
        if($id > 0){
            $array['id'] = 'exists:topics,id';
            $request->validate($array);   
            
            $topic = COLLECT(\DB::SELECT("select t.*, s.id speaker_id, s.name speaker_name, s.position speaker_position, s.company speaker_company, s.profile_path, s.company_path,
                        (CASE WHEN sr.role = 1 THEN 'main' WHEN sr.role = 2 THEN 'guest' END) as role_code,
                        (CASE WHEN t.status = 0 THEN 'deleted' WHEN t.status = 1 THEN 'active' END) as status_code
                        from topics t
                        left join speaker_roles sr ON t.id = sr.topicId
                        left join speakers s ON s.id = t.speakerId
                        where t.status <> 0 and sr.status <> 0 and s.status <> 0
                        and t.id = $id"))->first();
            
        }else{
            $request->validate($array);

            $topic = DB::SELECT("select t.*, s.id speaker_id, s.name speaker_name, s.position speaker_position, s.company speaker_company, s.profile_path, s.company_path,
                                (CASE WHEN sr.role = 1 THEN 'main' WHEN sr.role = 2 THEN 'guest' END) as role_code,
                                (CASE WHEN t.status = 0 THEN 'deleted' WHEN t.status = 1 THEN 'active' END) as status_code
                                from topics t
                                left join speaker_roles sr ON t.id = sr.topicId
                                left join speakers s ON s.id = t.speakerId
                                where t.status <> 0 and sr.status <> 0 and s.status <> 0");
            
        }

        return response(["topic(s)" => $topic], 200);

    }
}
