<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Course;
use App\Models\Module;
use App\Models\Speaker;
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

        return response()->json(["courses" => $courses], 200);
        
    }

    public function addModule(Request $request){
        
        $module = $request->validate([
            'courseId' => 'required|numeric|min:1|exists:Courses,id',
            'name' => 'required|string',
            'description' => 'required|string',
            'date' => 'required|date_format:Y-m-d',
            'starting_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s',
        ]);

        $checker = DB::SELECT("SELECT * FROM modules where courseId = $request->courseId and date = '$request->date' and status <> 0");

        if(!empty($checker)){
            return response(["message" => "record already exist. please double check the course id and date"], 409);
        }

        $module = Module::create(
            [
                'courseId' => $request->courseId,
                'name' => $request->name,
                'description' => $request->description,
                'date' => $request->date,
                'starting_time' => $request->starting_time,
                'end_time' => $request->end_time,
            ]);
        
        return response(["message" => "successfully added module's course", "module" => $module], 200);
        
    }
    public function updateModule($id, Request $request){
        $request->query->add(['id' => $id]);

        $request->validate([
            'id' => 'required|numeric|min:1|exists:Modules,id',
            'courseId' => 'numeric|min:1|exists:Courses,id',
            'name' => 'string',
            'description' => 'string',
            'date' => 'date_format:Y-m-d',
            'starting_time' => 'date_format:H:i:s',
            'end_time' => 'date_format:H:i:s',
        ]);

        if($request->status == "delete"){
            $request['status'] = 0;
        }elseif($request->status == "activate"){
            $request['status'] = 1;
        }
        

        $module = Module::find($id);
        
        $module->update($request->only('courseId', 'name', 'description', 'date', 'starting_time', 'end_time', 'status') +
                        [ 'updated_at' => now()]
                        );
                        
        return response(["message" => "successfully updated this module", "module" => $module], 200);

        
    }

    public function getModule($id, Request $request){

        $request->query->add(['id' => $id]);

        $module = $request->validate([
            'id' => 'required|numeric|min:1|exists:Modules,id'
        ]);

        $module = Module::find($request->id);

        $module->speakers = DB::SELECT("select *, (CASE WHEN status = 0 THEN 'deleted' WHEN status = 1 THEN 'active' END) status_code from speakers where moduleId = $request->id and status <> 0");

        return response(["module" => $module], 200);

    }

    public function addSpeaker(Request $request){
        $regex = "/[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)?/gi";
        
        $speaker = $request->validate([
            'moduleId' => 'required|numeric|min:1|exists:modules,id',
            'name' => 'required|string',
            'position' => 'string',
            'company' => 'string',
            'profile_path' => 'regex:'.$regex,
            'company_path' => 'regex:'.$regex,
            'role' => 'required|string',
        ]);
        $role = 0;
        
        ($request->role == "main")? $role = 1 : $role = 2;
        
        // check for duplicate main addSpeaker
        $checker = DB::SELECT("SELECT * FROM speakers where role = $role and status <> 0");

        if(!empty($checker) && $role == 1){
            return response(["message" => "main speaker already exist. please check the role"], 409);
        }

        $addSpeaker = Speaker::create($request->only('position', 'company', 'profile_path', 'company_path') +
                [
                    'moduleId' => $request->moduleId,
                    'name' => $request->name,
                    'role' => $role,
                ]);

        $speaker = DB::SELECT("SELECT *, (CASE WHEN role = 1 THEN 'main' WHEN role = 2 THEN 'guest' END) role_code FROM speakers where id = $addSpeaker->id");

        return response(["speaker" => $speaker], 200);

    }

    public function updateSpeaker($id, Request $request){
        
        $request->query->add(['id' => $id]);
        $regex = "/[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)?/gi";

        $speaker = $request->validate([
            'id' => 'required|numeric|min:1|exists:Speakers,id',
            'moduleId' => 'numeric|min:1|exists:modules,id',
            'name' => 'string',
            'position' => 'string',
            'company' => 'string',
            'profile_path' => 'regex:'.$regex,
            'company_path' => 'regex:'.$regex,
            'role' => 'string',
        ]);
        
        if($request->status == "delete"){
            $request['status'] = 0;
        }elseif($request->status == "activate"){
            $request['status'] = 1;
        }
        
        $role = 0;
        
        ($request->role == "main")? $role = 1 : $role = 2;
        
        // check for duplicate main addSpeaker
        $checker = DB::SELECT("SELECT * FROM speakers where role = $role and status <> 0");

        if(!empty($checker) && $role == 1){
            return response(["message" => "main speaker already exist. please check the role"], 409);
        }
        
        $updateSpeaker = Speaker::find($id);
        
        $updateSpeaker->update($request->only('name', 'position', 'company', 'profile_path', 'company_path', 'role', 'status') +
                        [ 'updated_at' => now()]
                        );

        $speaker = DB::SELECT("SELECT *, (CASE WHEN role = 1 THEN 'main' WHEN role = 2 THEN 'guest' END) role_code FROM speakers where id = $id");

        return response(["message" => "successfully updated this speaker", "speaker" => $speaker], 200);
    }

    
    public function getModules($id, Request $request){
        $request->query->add(['id' => $id]);

        $course = $request->validate([
            'id' => 'numeric|min:1|exists:Courses,id',
        ]);
        
        $course = Course::where('id', $request->id)->where('status', '<>', 0)->first();

        $modules = Module::where('courseId', $request->id)->where('status', '<>', 0)->get();

        foreach ($modules as $key => $value) {
            $value['speakers'] = Speaker::where('moduleId', $value->id)->where('status', '<>', 0)->get();
        }

        
        return response()->json(["course" => $course, "modules" => $modules], 200);

    }
    
    public function liveModule(Request $request){
        $request->validate([
            'id' => 'required|numeric|min:1|exists:Modules,id',
        ]);

        $liveModule = Module::find($request->id);
        
        $liveModule->update(
                        [ 
                            'is_live' => 1,
                            'updated_at' => now(),
                        ]
                        );

        return response(["message" => "module id $request->id is now live",], 200);
    }
}
