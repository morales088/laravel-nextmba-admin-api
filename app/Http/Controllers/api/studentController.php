<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Student;
use App\Models\Links;
use Validator;
use DB;

class studentController extends Controller
{
    public function index(Request $request){

        $login = $request->validate([
            'page' => 'required|numeric|min:1',
            'sort_column' => 'required|string',
            'sort_type' => 'required|string',
            'course' => 'string',
            'location' => 'string',
            'phone' => 'string',
            'company' => 'string',
            'position' => 'string',
            'interest' => 'string',
        ]);

        $query_filter = [];

        !empty($request->course)? $query_filter += ['course' => $request->course] : '';
        !empty($request->location)? $query_filter += ['location' => $request->location] : '';
        !empty($request->phone)? $query_filter += ['phone' => $request->phone] : '';
        !empty($request->company)? $query_filter += ['company' => $request->company] : '';
        !empty($request->position)? $query_filter += ['position' => $request->position] : '';
        !empty($request->interest)? $query_filter += ['interest' => $request->interest] : '';
        !empty($request->search)? $query_filter += ['search' => $request->search] : '';

        !empty($request->page)? $query_filter += ['page' => $request->page] : '';
        (!empty($request->sort_column) && !empty($request->sort_column) )? $query_filter += ['sort_column' => $request->sort_column, 'sort_type' => $request->sort_type] : '';

        // dd('asc' === 'ASC');
    
        $students = Student::getStudent($query_filter);

        // dd($students);
        
        foreach ($students as $key => $value) {

            $studentLinks = Student::getStudentLinks($value->id);
            $value->links = $studentLinks;
        }

        return response(["students" => $students], 200);
    }

    public function coursesByStudent(Request $request, $id){
        
        $request->query->add(['id' => $id]);

        $studentId = $request->validate([
            'id' => 'numeric|min:1|exists:Students,id',
        ]);

        $courses = DB::SELECT("select sc.studentId, c.id courseId, c.name, sc.created_at date_started, sc.expirationDate
                                from studentcourses sc
                                left join student_modules sm ON sm.id = sc.studentId
                                left join courses c ON c.id = sc.courseId
                                where sc.studentId = $id");

        foreach ($courses as $key => $value) {
            
            $modules = DB::SELECT("select sm.id, m.name module_name, sm.remarks, sm.status, (CASE WHEN sm.status = 1 THEN 'active' WHEN sm.status = 2 THEN 'pending'  WHEN sm.status = 2 THEN 'complete' END) as status_code, sm.updated_at
                                    from student_modules sm
                                    left join modules m ON m.id = sm.moduleId
                                    where sm.status <> 0 and m.courseId = $value->courseId and sm.studentId = $id");

            $value->modules = $modules;
        }

        return response(["coursesPerStudent" => $courses], 200);
    }

    public function modulePerCourses(Request $request, $courseId, $id){
        $request->query->add(['id' => $id, 'id' => $courseId]);

        $students = $request->validate([
            'id' => 'numeric|min:1|exists:Students,id',
            'moduleId' => 'numeric|min:1|exists:Students,id',
        ]);

        $modules = DB::SELECT("select sm.studentId, m.name module_name, sm.remarks, sm.status, (CASE WHEN sm.status = 1 THEN 'active' WHEN sm.status = 2 THEN 'pending'  WHEN sm.status = 2 THEN 'complete' END) as status_code, sm.updated_at
                                from student_modules sm
                                left join modules m ON m.id = sm.moduleId
                                where sm.status <> 0 and m.courseId = $courseId and sm.studentId = $id");


        return response(["modulePerCourses" => $modules], 200);
    }

    public function updateStudent(Request $request, $id){

        $students = Student::find($id);
        
        $students->update($request->only('name', 'email', 'phone', 'location', 'company', 'position', 'field') +
                        [ 'updated_at' => now()]
                        );

        $links = [];

        !empty($request->LI)? $links += ['li' => $request->LI] : '';
        !empty($request->IG)? $links += ['ig' => $request->IG] : '';
        !empty($request->FB)? $links += ['fb' => $request->FB] : '';
        !empty($request->TG)? $links += ['tg' => $request->TG] : '';
        !empty($request->WS)? $links += ['ws' => $request->WS] : '';

        foreach ($links as $key => $value) {
            // $link = collect(\DB::SELECT("SELECT * FROM links where studentId = $id and name = '$key'"))->first();

            $link = Links::where('studentId', $id)->where('name', $key)->first();
            
            if($link){
                $link->update(
                [ 
                    'link' => $value,
                    'updated_at' => now()
                ]
                );
            }else{
                Links::create($request->only('icon') + 
                [
                    'studentId' => $id,
                    'name' => $key,
                    'link' => $value
                ]);
            }

        }
        
        $newStudentInfos =  Student::find($id);

        $newStudentLinks =  Links::where('studentId', $id)->get();
        
        $newStudentInfos->links = $newStudentLinks;

        // dd($newStudentInfos, $newStudentLinks);

        return response(["student" => $newStudentInfos], 200);
    }

    public function activateDeactivate(Request $request, $id){
        
        $request->validate([
            'status' => 'string',
        ]);
        
        $status = 1;

        if($request->status == 'activate'){
            $status = 1;
        }elseif($request->status == 'deactivate'){
            $status = 0;
        }

        $students = Student::find($id);
        
        $students->update(
                    [ 
                        'updated_by' => auth('api')->user()->id,
                        'status' => $status,
                        // 'updated_at' => now()
                    ]
                    );

        return response(["message" => "successfully updated this student"], 200);

    }

    public function studentById(Request $request, $id){
        
        $request->query->add(['id' => $id]);

        $studentId = $request->validate([
            'id' => 'numeric|min:1|exists:Students,id',
        ]);
        
        $student = Student::where('id', $id)->first();

        $student['links'] = Links::where('studentId', $student->id)->get();

        return response()->json(["student" => $student], 200);
    }


    public function changePassword(Request $request, $id){

        $textPassword = Str::random(10);
        $hashPasword = Hash::make($textPassword);

        $students = Student::find($id);
        
        $students->update(
                        [ 
                            'password' => $hashPasword,
                            'updated_by' => auth('api')->user()->id,
                            // 'updated_at' => now()
                        ]
                    );

        return response(["newPassword" => $textPassword, "message" => "successfully updated this student"], 200);
        
    }
}
