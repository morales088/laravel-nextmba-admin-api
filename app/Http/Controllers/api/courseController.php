<?php

namespace App\Http\Controllers\api;

use DB;
use App\Models\User;
use App\Models\Topic;
use App\Models\Course;
use App\Models\Module;
use App\Models\Speaker;
use App\Models\Category;
use App\Models\Extravideo;
use App\Models\Modulefile;
use App\Models\Speakerrole;
use App\Models\ModuleStream;
use App\Models\ReplayVideo;
use App\Models\ModelLanguage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class courseController extends Controller
{
    public function index(Request $request){

        // $courses = DB::SELECT("select c.id course_id, c.name, c.price course_price, c.description, count(s.id) total_students, s.status, (CASE WHEN s.status = 0 THEN 'deleted' WHEN s.status = 1 THEN 'active' END) as status_code, c.created_at, c.updated_at
        //                         from students s
        //                         left join studentcourses sc ON sc.studentId = s.id
        //                         left join courses c ON c.id = sc.courseId
        //                         where s.status <> 0 and sc.status <> 0 and c.status <> 0
        //                         group by c.id");

        $courses = DB::SELECT("select c.id course_id, c.name, c.price course_price, c.description, is_displayed, c.created_at, c.updated_at
                            from courses c 
                            where c.status <> 0");
                            
        foreach ($courses as $key => $value) {

            $count = DB::SELECT("select count(s.id) total_students, s.status, (CASE WHEN s.status = 0 THEN 'deleted' WHEN s.status = 1 THEN 'active' END) as status_code
                                from students s
                                left join studentcourses sc ON sc.studentId = s.id
                                where s.status <> 0 and sc.status <> 0 and sc.courseId = $value->course_id");
            // dd($count);
            if(!empty($count)){
                $value->total_students = $count[0]->total_students;
                $value->status = $count[0]->status;
                $value->status = $count[0]->status_code;
            }else{
                $value->total_students = 0;
                $value->status = 0;
                $value->status = 'deleted';
            }

        }

        // $courses = DB::SELECT("select *, (CASE WHEN m.status = 0 THEN 'deleted' WHEN m.status = 1 THEN 'active' END) as status_code, concat(m.date, '', m.starting_time) start_date
        // from modules m");

        return response()->json(["courses" => $courses], 200);
        
    }

    public function addModule(Request $request){
        $regex = "/^((?:https?\:\/\/|www\.)(?:[-a-z0-9]+\.)*[-a-z0-9]+.*)$/";
        
        $module = $request->validate([
            'courseId' => 'numeric|min:1|exists:courses,id',
            'name' => 'string',
            'category_id' => 'nullable|numeric',
            // 'category' => 'string|nullable|sometimes',
            // 'category_color' => 'string|nullable|sometimes',
            'zoom_link' => 'string|nullable|sometimes',
            // 'description' => 'string',
            // 'chat_url' => 'string', // 'regex:'.$regex,
            // 'live_url' => 'string', // 'regex:'.$regex,
            'topic' => 'string',
            // 'calendar_link' => 'string', // 'regex:'.$regex,
            // 'date' => 'date_format:Y-m-d',
            'start_date' => 'date_format:Y-m-d H:i:s',
            'end_date' => 'date_format:Y-m-d H:i:s',
            // 'module_image' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);
        
        $checker = DB::SELECT("SELECT * FROM modules where courseId = $request->courseId and start_date = '$request->start_date' and status <> 0");

        if(!empty($checker)){
            return response(["message" => "record already exist. please double check the course id and date"], 409);
        }

        if(!empty($request->description)){
            $description = urlencode($request->description);
            $request->merge([
                'description' => $description,
            ]);

        }

        // if(!empty($request->module_image) || !empty($request->module_link)){
        //     $path = Module::moduleImage($request->all());
                
        //     $request->query->add(['cover_photo' => $path]);
        // }
        
        $module = Course::createModule($request);

        
        
        return response(["message" => "successfully added module's course", "module" => $module], 200);
        
    }

    public function updateModule(Request $request,$id){
        $regex = "/^((?:https?\:\/\/|www\.)(?:[-a-z0-9]+\.)*[-a-z0-9]+.*)$/";
        $request->query->add(['id' => $id]);

        $request->validate([
            'id' => 'required|numeric|min:1|exists:modules,id',
            'courseId' => 'numeric|min:1|exists:courses,id',
            'name' => 'string',
            'category_id' => 'nullable|numeric',
            // 'category' => 'string|nullable|sometimes',
            // 'category_color' => 'string|nullable|sometimes',
            'zoom_link' => 'string|nullable|sometimes',
            // 'description' => 'string',
            // 'chat_url' => 'string', // 'regex:'.$regex,
            // 'live_url' => 'string', // 'regex:'.$regex,
            'topicId' => 'numeric|min:1|exists:topics,id',
            // 'calendar_link' => 'string', // 'regex:'.$regex,
            // 'date' => 'date_format:Y-m-d',
            'start_date' => 'date_format:Y-m-d H:i:s',
            'end_date' => 'date_format:Y-m-d H:i:s',
            'module_cover_image' => 'image|mimes:jpeg,png,jpg|max:2048',
            'display_topic' => 'in:1,0',
            'pro_access' => 'in:1,0',
            // 'module_status' => [
            //             'string',
            //             Rule::in(['draft', 'published', 'archived']),
            //         ],
            // 'broadcast_status' => [
            //             'string',
            //             Rule::in(['offline', 'live', 'pending_replay', 'replay']),
            //         ],
        ]);
        
        // if($request->module_status == "draft"){
        //     $request['status'] = 1;
        // }elseif($request->module_status == "published"){
        //     $request['status'] = 2;
        // }elseif($request->module_status == "archived"){
        //     $request['status'] = 3;
        // }

        // if($request->broadcast_status == "offline"){
        //     $request['broadcast_status'] = 1;
        // }elseif($request->broadcast_status == "live"){
        //     $request['broadcast_status'] = 2;
        // }elseif($request->broadcast_status == "pending_replay"){
        //     $request['broadcast_status'] = 3;
        // }elseif($request->broadcast_status == "replay"){
        //     $request['broadcast_status'] = 4;
        // }
        // dd($request->all());
        $module = DB::transaction(function() use ($request, $id) {
        
            
            if(!empty($request->description)){
                $description = urlencode($request->description);
                $request->merge([
                    'description' => $description,
                ]);

            }
            
            if(!empty($request->module_cover_image) || !empty($request->module_cover_link)){
                $path = Module::moduleImage($request->all(), $id);
            }else{
                $request->query->add(['cover_photo' => $request->module_cover_link]);
            }

            $module = Module::find($id);

            // update speaker role
            if($request->topicId){
                $topic_roles = DB::SELECT("select sr.*
                                            from modules m
                                            left join topics t on t.moduleId = m.id
                                            left join speaker_roles sr on sr.topicId = t.id
                                            where m.status <> 0 and t.status <> 0 and sr.status <> 0 and 
                                            m.id = $id");
                foreach ($topic_roles as $key => $value) {
                    
                    if($value->id == $request->topicId){
                        $role = ['role' => "1"];
                    }else{
                        $role = ['role' => "2"];
                    }
                    DB::table('speaker_roles')
                        ->where('id', $value->id)
                        ->update($role);


                }
            }
            
            $module->update($request->only('courseId', 'name', 'description', 'category', 'cover_photo', 'chat_url', 'live_url', 'topicId', 'category', 'category_color', 'pro_access', 'display_topic', 'zoom_link', 'category_id',
                                            'calendar_link', 'start_date', 'end_date') +
                            [ 'updated_at' => now()]
                            );
                            
            $getmodule = COLLECT(\DB::SELECT("select m.*, 
                                            (CASE WHEN m.status = 1 THEN 'draft' WHEN m.status = 2 THEN 'published' WHEN m.status = 3 THEN 'archived' END) module_status,
                                            (CASE WHEN m.broadcast_status = 1 THEN 'offline' WHEN m.broadcast_status = 2 THEN 'live' WHEN m.broadcast_status = 3 THEN 'pending_replay' WHEN m.broadcast_status = 4 THEN 'replay' END) broadcast_status,
                                            t.name topic_name
                                            from modules m       
                                            left join topics t ON t.id = m.topicId
                                            where m.id = $module->id and m.status <> 0 or t.status <> 0"))->first();

            $getmodule->description = urldecode($getmodule->description);                     

            return $getmodule;
        
        });

        return response(["message" => "successfully updated this module", "module" => $module], 200);

        
    }

    public function getModule(Request $request, $id){

        $request->query->add(['id' => $id]);

        $module = $request->validate([
            'id' => 'required|numeric|min:1|exists:modules,id'
        ]);
        
        // $module = Module::where('id', $request->id)->selectRaw("*, (CASE WHEN status = 0 THEN 'deleted' WHEN status = 1 THEN 'active' END) status_code")->first();

        // $module->speakers = DB::SELECT("select *, (CASE WHEN role = 1 THEN 'main' WHEN role = 2 THEN 'guest' END) role_code
        //                                         , (CASE WHEN status = 0 THEN 'deleted' WHEN status = 1 THEN 'active' END) status_code
        //                                 from speakers where moduleId = $request->id and status <> 0");

        // $module = COLLECT(\DB::SELECT("select * from (select m.id, m.courseId, m.name, m.description, m.cover_photo, m.chat_url, m.live_url, m.topicId, m.calendar_link, m.start_date, m.end_date,
        //                                 (CASE WHEN m.status = 1 THEN 'draft' WHEN m.status = 2 THEN 'published' WHEN m.status = 3 THEN 'archived' END) module_status,
        //                                 (CASE WHEN m.broadcast_status = 1 THEN 'offline' WHEN m.broadcast_status = 2 THEN 'live' WHEN m.broadcast_status = 3 THEN 'pending_replay' WHEN m.broadcast_status = 4 THEN 'replay' END) broadcast_status,
        //                                 t.name topic_name
        //                                 from modules m 
        //                                 left join topics t ON m.id = t.moduleId and t.id = m.topicId
        //                                 where m.status <> 0 or t.status <> 0
        //                                 group by m.id) m where m.id = $id"))->first();

        $module = COLLECT(\DB::SELECT("select * from (select m.id, m.courseId, m.name, m.description, m.category_id, m.category, m.category_color, m.cover_photo, m.chat_url, m.live_url, m.topicId, m.calendar_link, m.start_date, m.end_date, m.pro_access, m.display_topic, m.zoom_link,
                                        (CASE WHEN m.status = 1 THEN 'draft' WHEN m.status = 2 THEN 'published' WHEN m.status = 3 THEN 'archived' END) module_status,
                                        (CASE WHEN m.broadcast_status = 0 THEN 'start_server' WHEN m.broadcast_status = 1 THEN 'offline' WHEN m.broadcast_status = 2 THEN 'live' WHEN m.broadcast_status = 3 THEN 'pending_replay' WHEN m.broadcast_status = 4 THEN 'replay' END) broadcast_status,
                                        t.name topic_name, m.stream_info, m.stream_json, m.uid, m.srt_url
                                        from modules m 
                                        left join topics t ON m.id = t.moduleId and t.id = m.topicId
                                        where m.status <> 0 or t.status <> 0
                                        group by m.id) m where m.id = $id"))->first();
                                        
        $topics = DB::SELECT("SELECT t.*, s.name speaker_name, s.position speaker_position, s.company speaker_company, s.profile_path speaker_profile_path, s.company_path speaker_company_path,
                                    (CASE WHEN t.status = 0 THEN 'deleted' WHEN t.status = 1 THEN 'active' END) as status_code
                                    FROM topics t
                                    LEFT JOIN speakers s ON s.id = t.speakerId
                                    where t.status <> 0 and s.status <> 0 and t.moduleID = $module->id");

        $module->description = urldecode($module->description);
        $module->topics = $topics;

        $category = Category::where('status', '<>', 0)
            ->whereIn('id', [$module->category_id])
            ->get();
        
        $module->category = $category;

        return response(["module" => $module], 200);

    }

    
    public function getModules(Request $request, $id){
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

        $modules = DB::SELECT("select * from (select m.id, m.courseId, m.name, m.description, m.category_id, m.category, m.category_color, m.cover_photo, m.chat_url, m.live_url, m.topicId, m.calendar_link, m.zoom_link, m.start_date, m.end_date,
                                (CASE WHEN m.status = 1 THEN 'draft' WHEN m.status = 2 THEN 'published' WHEN m.status = 3 THEN 'archived' END) module_status,
                                (CASE WHEN m.broadcast_status = 1 THEN 'offline' WHEN m.broadcast_status = 2 THEN 'live' WHEN m.broadcast_status = 3 THEN 'pending_replay' WHEN m.broadcast_status = 4 THEN 'replay' END) broadcast_status,
                                t.name topic_name, m.status module_status_code, m.broadcast_status broadcast_status_code
                                from modules m 
                                left join topics t ON m.id = t.moduleId and t.id = m.topicId
                                where m.status <> 0 or t.status <> 0
                                group by m.id) m where m.courseId = $id order by m.start_date desc");

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
        $regex = "/^((?:https?\:\/\/|www\.)(?:[-a-z0-9]+\.)*[-a-z0-9]+.*)$/";
        
        $speaker = $request->validate([
            'moduleId' => 'required|numeric|min:1|exists:modules,id',
            'speakerId' => 'required|numeric|min:1|exists:speakers,id',
            'name' => 'required|string',
            'vimeo_url' => 'string|sometimes|nullable',
            // 'video_link' => 'string', // 'regex:'.$regex,
            // 'description' => 'string',
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
                                where t.moduleId = $request->moduleId and sr.role = 1 and t.status <> 0");
        // dd($checker, $role, (!empty($checker) && $role == 1));
                                
        if((!empty($checker) && $role == 1) ){
            return response(["message" => "main speaker / speaker already exists"], 409);
        }

        $DBtransaction = DB::transaction(function() use ($request, $role) {
            
            if(!empty($request->description)){
                $description = urlencode($request->description);
                $request->merge([
                    'description' => $description,
                ]);

            }

            // insert to topics table

            $addTopic = Topic::create($request->only('video_link', 'vimeo_url', 'description', 'uid') +
                [
                    'moduleId' => $request->moduleId,
                    'speakerId' => $request->speakerId,
                    'name' => $request->name,
                ]);


            // insert to speakers_topic table
            $addSpeakertopic = Speakerrole::create(
                [
                    'topicId' => $addTopic->id,
                    'speakerId' => $request->speakerId,
                    'role' => $role,
                ]);

                $topic = collect(\DB::SELECT("select t.*, s.id speakerId, s.name speaker_name, s.position speaker_position, s.company speaker_company, s.profile_path speaker_profile_path, s.company_path speaker_company_path, 
                            (CASE WHEN role = 0 THEN 'delete' WHEN role = 1 THEN 'active' END) topic_status, t.uid topic_uid
                            from topics t
                            left join speaker_roles sr on t.id = sr.topicId
                            left join speakers s ON s.id = t.speakerId
                            where t.moduleId = $request->moduleId and t.speakerId = $request->speakerId and t.status <> 0 and s.status <> 0"))->first();
                            
                $topic->description = urldecode($topic->description);
                
                return $topic;
            
        });

        return response(["topic" => $DBtransaction], 200);

    }

    public function updateTopic($id, Request $request){
        
        $request->query->add(['id' => $id]);
        $regex = "/^((?:https?\:\/\/|www\.)(?:[-a-z0-9]+\.)*[-a-z0-9]+.*)$/";
        
        $request->validate([
            'id' => 'required|numeric|min:1|exists:topics,id',
            'moduleId' => 'required|numeric|min:1|exists:modules,id',
            'speakerId' => 'numeric|min:1|exists:speakers,id',
            'name' => 'string',
            'vimeo_url' => 'string|sometimes|nullable',
            // 'video_link' => 'string', // 'regex:'.$regex,
            // 'description' => 'string',
            // 'speaker_role' => [
            //             'string',
            //             Rule::in(['main', 'guest']),
            //         ],
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

        // $role = 0;
            
        // ($request->speaker_role == "main")? $role = 1 : $role = 2;

        // // check duplicate main speaker
        // $checker = DB::SELECT("select *
        //                         from topics t
        //                         left join speaker_roles sr on t.id = sr.topicId
        //                         where t.moduleId = $request->moduleId and t.speakerId = $request->speakerId and sr.role = 1 and t.status <> 0");

        // if(!empty($checker) && $role == 1){
        //     return response(["message" => "speaker already exists"], 409);
        // }

        // dd($request->all());

        $DBtransaction = DB::transaction(function() use ($request) {

            
            if(!empty($request->description)){
                $description = urlencode($request->description);
                $request->merge([
                    'description' => $description,
                ]);

            }
        
            $updateTopic = Topic::find($request->id);
            
            // dd($updateTopic);
        
            $updateTopic->update($request->only('moduleId', 'speakerId', 'name', 'video_link', 'vimeo_url', 'uid', 'description', 'status') +
                            [ 'updated_at' => now()]
                            );
                            

            // // update speker_role
            // if(!empty($request->speaker_role)){

            //     DB::table('speaker_roles')
            //     ->where('topicId', $request->id)
            //     ->where('speakerId', $request->speakerId)
            //     ->update(['role' => $role]);
                
            // }

            // $topic = collect(\DB::SELECT("select t.*, s.id speakerId, s.name speaker_name, s.position speaker_position, s.company speaker_company, s.profile_path speaker_profile_path, s.company_path speaker_company_path, 
            //                 (CASE WHEN role = 0 THEN 'delete' WHEN role = 1 THEN 'active' END) topic_status
            //                 from topics t
            //                 left join speaker_roles sr on t.id = sr.topicId
            //                 left join speakers s ON s.id = t.speakerId
            //                 where t.moduleId = $request->moduleId and t.speakerId = $request->speakerId and sr.role = 1 and t.status <> 0 and s.status <> 0"))->first();

            $topic = collect(\DB::SELECT("select t.*, s.id speakerId, s.name speaker_name, s.position speaker_position, s.company speaker_company, s.profile_path speaker_profile_path, s.company_path speaker_company_path, 
                                            (CASE WHEN role = 0 THEN 'delete' WHEN role = 1 THEN 'active' END) topic_status, t.uid topic_uid
                                            from topics t
                                            left join speaker_roles sr on t.id = sr.topicId
                                            left join speakers s ON s.id = t.speakerId
                                            where t.id = $request->id and s.status <> 0"))->first();
            
            $topic->description = urldecode($topic->description);

            return $topic;
        });

        return response(["topic" => $DBtransaction], 200);

    }

    public function getTopic(Request $request,$moduleId, $id = 0){
        
        $request->query->add(['id' => $id]);
        $request->query->add(['moduleId' => $moduleId]);

        $array = [
                'moduleId' => 'required|exists:modules,id',
                ];

        
        if($id > 0){
            $array['id'] = 'exists:topics,id';
            $request->validate($array);   
            
            $topic = COLLECT(\DB::SELECT("select t.*, s.id speakerId, s.name speaker_name, s.position speaker_position, s.company speaker_company, s.profile_path, s.company_path,
                        (CASE WHEN sr.role = 1 THEN 'main' WHEN sr.role = 2 THEN 'guest' END) as role_code,
                        (CASE WHEN t.status = 0 THEN 'deleted' WHEN t.status = 1 THEN 'active' END) as status_code,
                        t.uid topic_uid
                        from topics t
                        left join speaker_roles sr ON t.id = sr.topicId
                        left join speakers s ON s.id = t.speakerId
                        where t.status <> 0 and sr.status <> 0 and s.status <> 0
                        and t.id = $id"))->first();

            $topic->description = urldecode($topic->description);
            
        }else{
            $request->validate($array);

            $topic = DB::SELECT("select t.*, s.id speakerId, s.name speaker_name, s.position speaker_position, s.company speaker_company, s.profile_path, s.company_path,
                                (CASE WHEN sr.role = 1 THEN 'main' WHEN sr.role = 2 THEN 'guest' END) as role_code,
                                (CASE WHEN t.status = 0 THEN 'deleted' WHEN t.status = 1 THEN 'active' END) as status_code
                                from topics t
                                left join speaker_roles sr ON t.id = sr.topicId
                                left join speakers s ON s.id = t.speakerId
                                where t.moduleId = $moduleId and t.status <> 0 and sr.status <> 0 and s.status <> 0");
            
            
            foreach ($topic as $key => $value) {
                // dd($value->description);
                $value->description = urldecode($value->description);
            }
            
        }

        return response(["topics" => $topic], 200);

    }

    public function updateModuleStatus(Request $request,$id){
        $regex = "/^((?:https?\:\/\/|www\.)(?:[-a-z0-9]+\.)*[-a-z0-9]+.*)$/";
        $request->query->add(['id' => $id]);

        $request->validate([
            'id' => 'required|numeric|min:1|exists:modules,id',
            'module_status' => [
                        'string',
                        Rule::in(['draft', 'published', 'archived']),
                    ],
            'broadcast_status' => [
                        'string',
                        Rule::in(['start_server', 'offline', 'live', 'pending_replay', 'replay']),
                    ],
        ]);
        
        
        if($request->module_status == "draft"){
            $request['status'] = 1;
        }elseif($request->module_status == "published"){
            $request['status'] = 2;
        }elseif($request->module_status == "archived"){
            $request['status'] = 3;
        }

        if($request->broadcast_status == "start_server"){
            $request['broadcast_status'] = 0;
        }elseif($request->broadcast_status == "offline"){
            $request['broadcast_status'] = 1;
        }elseif($request->broadcast_status == "live"){
            $request['broadcast_status'] = 2;
        }elseif($request->broadcast_status == "pending_replay"){
            $request['broadcast_status'] = 3;
        }elseif($request->broadcast_status == "replay"){
            $request['broadcast_status'] = 4;
        }
        
        if($request->status > 1){
            // check if all speaker has picture
            $check = DB::SELECT("select IF(s.profile_path IS NULL or s.profile_path = '', false, true) as profile_existence
                                    from modules m
                                    left join topics t on t.moduleId = m.id
                                    left join speakers s on s.id = t.speakerId
                                    where m.status <> 0 and t.status <> 0 and s.status <> 0 and 
                                    m.id = $id");
            if(empty($check)){
                return response(["message" => "no topic(s) found in this module",], 422);
            }

            foreach ($check as $key => $value) {
                if(!$value->profile_existence){
                    return response(["message" => "cannot leave blank on speaker's picture",], 422);
                }
            }
        }

        $module = Module::find($id);
        $module->update($request->only('broadcast_status', 'status') +
                            [ 'updated_at' => now()]
                            );

        // dd($request->all());
        return response(["message" => "successfully updated this module", "module" => $module], 200);

    }

    public function addZoom(Request $request){
        $regex = "/^((?:https?\:\/\/|www\.)(?:[-a-z0-9]+\.)*[-a-z0-9]+.*)$/";
        
        $request->validate([
            'module_id' => 'required|numeric|min:1|exists:modules,id',
            'title' => 'required|string',
            // 'image_url' => 'string', // 'regex:'.$regex,
            // 'replay_url' => 'string', // 'regex:'.$regex,
            // 'description' => 'string', // 'regex:'.$regex,
            'video_image' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $request->query->add(['moduleId' => $request->module_id]);
        
        
        if(!empty($request->video_image) || !empty($request->video_image_link)){
            $video_image = Extravideo::extaImage($request->all());
                
            $request->query->add(['image_url' => $video_image]);
        }

        $video = Extravideo::create($request->only('moduleId', 'image_url', 'replay_url', 'description') +
                [
                    // 'module_id' => $request->module_id,
                    'title' => $request->title,
                ]);

        return response(["video" => $video], 200);
    }

    public function updateZoom(Request $request, $id){
        $request->query->add(['id' => $id]);
        $regex = "/^((?:https?\:\/\/|www\.)(?:[-a-z0-9]+\.)*[-a-z0-9]+.*)$/";
        
        $request->validate([
            'id' => 'required|numeric|min:1|exists:extra_videos,id',
            'title' => 'string',
            'status' => [
                        'string',
                        Rule::in(['delete', 'active']),
                    ],
            'video_image' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if($request->status == "delete"){
            $request['status'] = 0;
        }elseif($request->status == "active"){
            $request['status'] = 1;
        }

        
        if(!empty($request->video_image) || !empty($request->video_image_link)){
            $video_image = Extravideo::extaImage($request->all(), $request->id);
        }

        $updateVideo = Extravideo::find($request->id);
            
        // dd($updateVideo);
    
        $updateVideo->update($request->only('title', 'image_url', 'replay_url', 'description', 'status') +
                        [ 'updated_at' => now()]
                        );

        return response(["video" => $updateVideo], 200);

    }

    public function getZoom(Request $request, $module_id, $id = 0){
        $request->query->add(['module_id' => $module_id]);
        $request->query->add(['id' => $id]);

        $request->validate([
            'module_id' => 'required|numeric|min:1|exists:modules,id',
            // 'id' => 'min:1|exists:extra_videos,id',
        ]);

        if($id > 0){
            $video = COLLECT(\DB::SELECT("SELECT * FROM extra_videos where id = $id and status <> 0"))->first();
        }else{
            $video = DB::SELECT("SELECT * FROM extra_videos where moduleId = $request->module_id and status <> 0");
        }


        return response(["videos" => $video], 200);
    }

    public function getFiles(Request $request, $module_id = 0){
                
        $request->query->add(['module_id' => $module_id]);
        
        $request->validate([
            'module_id' => 'required|exists:modules,id',
        ]);

        
        if($module_id > 0){
            
            $files = DB::SELECT("select * from module_files where moduleId = $module_id and status <> 0");
            
        }else{

            $files = DB::SELECT("select * from module_files where status <> 0");

        }
        return response(["files" => $files], 200);
    }

    public function addFiles(Request $request){

        $request->validate([
            'module_id' => 'required|exists:modules,id',
            'name' => 'string',
            'type' => 'in:0,1'
        ]);
        // dd($request->all());

        if(!empty($request->module_file)){
            $uploadFile = Modulefile::uploadFiles($request);
            $request->query->add(['link' => $uploadFile]);
        }

        $files = Modulefile::create($request->only('link') + 
                [
                    'moduleId' => $request->module_id,
                    'name' => $request->name,
                    'type' => $request->type
                ]);

        return response(["files" => $files], 200);
        
    }

    public function updateFiles(Request $request, $id){

        $request->query->add(['id' => $id]);
        
        $request->validate([
            'id' => 'required|exists:module_files,id',
            'status' => [
                        'string',
                        Rule::in(['delete', 'active']),
                    ],
            'file_delete' => [
                        'string',
                        Rule::in(['true', 'false']),
                    ],
            'type' => 'in:0,1'
        ]);

        if(!empty($request->module_file)){
            $uploadFile = Modulefile::uploadFiles($request);
            $request->query->add(['link' => $uploadFile]);
        }
        
        if($request->file_delete == true){
            $request->query->add(['link' => null]);
        }

        if($request->status == "delete"){
            $request['status'] = 0;
        }elseif($request->status == "active"){
            $request['status'] = 1;
        }

        $file = Modulefile::find($request->id);
    
        $file->update($request->only('name', 'link', 'type', 'status') +
                        [ 'updated_at' => now()]
                        );

        return response(["file" => $file], 200);
        
    }
    
    public function addCourse(Request $request){
        
        $request->validate([
            'name' => 'required|string',
            'course_image' => 'image|mimes:jpeg,png,jpg|max:2048',
            'is_displayed' => 'in:0,1',
            'paid' => 'in:0,1'
        ]);


        if(!empty($request->course_image) || !empty($request->course_image_link)){
            $path = Course::courseImage($request->all());
                
            $request->query->add(['image_link' => $path]);
        }

        // dd($request->all());

        $course = Course::create($request->only('description', 'cover_photo', 'price', 'telegram_link', 'course_link', 'image_link', 'is_displayed', 'paid') + 
                                        [
                                            'name' => $request->name,
                                        ]);

        return response(["course" => $course], 200);
    }

    public function updateCourse(Request $request){
        
        $request->validate([
            'course_id' => 'required|numeric|min:1|exists:courses,id',
            'course_image' => 'image|mimes:jpeg,png,jpg|max:2048',
            'is_displayed' => 'in:0,1',
            'paid' => 'in:0,1'
        ]);

        if(!empty($request->course_image) || !empty($request->course_image_link)){
            $path = Course::courseImage($request->all(), $request->course_id);
        }

        $course = Course::find($request->course_id);

        $course->update($request->only('name', 'description', 'cover_photo', 'price', 'telegram_link', 'course_link', 'image_link', 'is_displayed', 'paid') + 
                        [ 
                            'updated_at' => now()
                        ]
                        );
                        
        return response(["course" => $course], 200);

    }

    public function getModuleStream(Request $request, $id){
        $request->query->add(['module_id' => $id]);

        $request->validate([
            'module_id' => 'required|numeric|min:1|exists:modules,id',
        ]);

        $streams = ModuleStream::where('module_id', $request->module_id)
                                ->where('status', '<>', 0)
                                ->get();
                                
        return response(["module_streams" => $streams], 200);

    }

    public function createModuleSteam(Request $request){

        $request->validate([
            'module_id' => 'required|numeric|min:1|exists:modules,id',
            'name' => 'required|string',
            // 'key' => 'required|string',
            // 'language' => 'in:1,2',
            'type' => 'in:1,2,3,4',
            // 'broadcast_status' => 'in:0,1,2,3,4',
            'status' => 'in:1,2,3,4'
        ]);

        $stream = ModuleStream::create($request->only('key', 'language', 'chat_link', 'status') + 
                                        [
                                            'module_id' => $request->module_id,
                                            'name' => $request->name,
                                            // 'key' => $request->key,
                                            'type' => $request->type,
                                        ]);
                        
        return response(["module_stream" => $stream], 200);
    }

    public function updateModuleSteam(Request $request, $id){
        $request->query->add(['stream_id' => $id]);

        $request->validate([
            'stream_id' => 'required|numeric|min:1|exists:module_streams,id',
            'name' => 'string',
            // 'key' => 'string',
            // 'chat_link' => 'string',
            // 'language' => 'in:1,2',
            'type' => 'in:1,2,3,4'
        ]);                                        

        $stream = ModuleStream::find($request->id);
    
        $stream->update($request->only('name', 'key', 'chat_link', 'language', 'type', 'status') +
                        [ 'updated_at' => now()]
                        );
                        
        return response(["module_stream" => $stream], 200);
    }

    public function getReplayVideo(Request $request, $id){
        $request->query->add(['module_id' => $id]);

        $request->validate([
            'module_id' => 'required|numeric|min:1|exists:modules,id',
        ]);

        $replays = DB::TABLE('topics as t')
                        ->leftJoin('replay_videos as rv', 't.id', '=', 'rv.topic_id')
                        ->where('t.status', 1)
                        ->where('rv.status', '<>', 0)
                        ->where('t.moduleId', $id)
                        ->select('rv.*')
                        ->orderBy('t.id')
                        ->get();

        // $request->query->add(['topic_id' => $id]);

        // $request->validate([
        //     'topic_id' => 'required|numeric|min:1|exists:topics,id',
        // ]);

        // $replays = ReplayVideo::where('topic_id', $request->topic_id)
        //                         ->where('status', '<>', 3)
        //                         ->get();
                                
        return response(["replays" => $replays], 200);

    }

    public function createReplayVideo(Request $request){

        $request->validate([
            'topic_id' => 'required|numeric|min:1|exists:topics,id',
            'name' => 'required|string',
            'stream_link' => 'required|string',
            // 'language' => 'in:1,2',
            'type' => 'in:1,2,3,4',
            'status' => 'in:0,1,2'
        ]);

        $replay = ReplayVideo::create($request->only('status') + 
                                        [
                                            'topic_id' => $request->topic_id,
                                            'name' => $request->name,
                                            'stream_link' => $request->stream_link,
                                            'language' => $request->language,
                                            'type' => $request->type,
                                        ]);
                        
        return response(["replay" => $replay], 200);
    }

    public function updateReplayVideo(Request $request, $id){
        $request->query->add(['replay_id' => $id]);

        $request->validate([
            'replay_id' => 'required|numeric|min:1|exists:replay_videos,id',
            'name' => 'string',
            'stream_link' => 'string',
            // 'language' => 'in:1,2',
            'type' => 'in:1,2,3,4'
        ]);
        
        $replay = ReplayVideo::find($request->id);
    
        $replay->update($request->only('name', 'stream_link', 'language', 'type', 'status') +
                        [ 'updated_at' => now()]
                        );
                        
        return response(["replay" => $replay], 200);
    }

    public function deleteReplayVideo(Request $request, $id) {
    
        $replay = ReplayVideo::find($id);
        
        if (!$replay) {
            return response(["error" => "Replay video not found."], 404);
        }
    
        $replay->delete();
    
        return response(["message" => "Replay video deleted successfully"], 200);
    }
    

    public function getModuleLanguage(Request $request, $id){
        $request->query->add(['module_id' => $id]);

        $request->validate([
            'module_id' => 'required|numeric|min:1|exists:modules,id',
        ]);

        $module_languages = ModelLanguage::where('module_id', $request->module_id)
                                        ->where('status', 1)
                                        ->get();

        return response(["module_languages" => $module_languages], 200);

    }

    public function createModuleLanguage(Request $request){

        $request->validate([
            'module_id' => 'required|numeric|min:1|exists:modules,id',
            'language' => 'required',
            'name' => 'required|string',
            // 'description' => 'required|string',
            'status' => 'in:0,1',
        ]);
        
        $module_language = ModelLanguage::create($request->only('description', 'status') + 
                                        [
                                            'module_id' => $request->module_id,
                                            'language' => $request->language,
                                            'name' => $request->name,
                                        ]);
                        
        return response(["module_language" => $module_language], 200);

    }

    public function updateModuleLanguage(Request $request, $id){
        $request->query->add(['Mlanguage_id' => $id]);

        $request->validate([
            'Mlanguage_id' => 'required|numeric|min:1|exists:module_languages,id',
            // 'module_id' => 'required|numeric|min:1|exists:modules,id',
            // 'language' => 'required',
            'name' => 'string',
            'description' => 'string',
            'status' => 'in:0,1',
        ]);
        
        $module_language = ModelLanguage::find($request->id);
    
        $module_language->update($request->only('language', 'name', 'description', 'status') +
                        [ 'updated_at' => now()]
                        );
                        
        return response(["module_language" => $module_language], 200);

    }
}
