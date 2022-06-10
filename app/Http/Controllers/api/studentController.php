<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Studentmodule;
use App\Models\Studentcourse;
use App\Models\Student;
use App\Models\Links;
use App\Models\Course;
use App\Models\Module;
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

        $total_students = Student::where('status', '<>', 0)->count();
        // dd($total_students);

        return response(["students" => $students, "total_students" => $total_students], 200);
    }

    public function coursesByStudent(Request $request, $id){
        
        $request->query->add(['id' => $id]);

        $studentId = $request->validate([
            'id' => 'numeric|min:1|exists:students,id',
        ]);
        
        $totalModules = 0;
        $courses = DB::SELECT("select sc.studentId, c.id courseId, c.name, sc.starting date_started, sc.expirationDate
                                from studentcourses sc
                                left join student_modules sm ON sm.id = sc.studentId
                                left join courses c ON c.id = sc.courseId
                                where sc.studentId = $id");

        foreach ($courses as $key => $value) {
            $value->totalModules = ++$totalModules;

            $completedModules = 0;

            $modules = DB::SELECT("select sm.id, m.name module_name, sm.remarks, sm.status, 
                                    (CASE WHEN sm.status = 0 THEN 'deleted' WHEN sm.status = 1 THEN 'active' WHEN sm.status = 2 THEN 'pending' WHEN sm.status = 3 THEN 'completed' END) as status_code, sm.updated_at
                                    from student_modules sm
                                    left join modules m ON m.id = sm.moduleId
                                    where sm.status <> 0 and m.courseId = $value->courseId and sm.studentId = $id");
            foreach ($modules as $key2 => $value2) {
                if($value2->status == 3){ $completedModules++; }
            }
            
            $value->completedModules = $completedModules;
            $value->modules = $modules;
        }

        return response(["coursesPerStudent" => $courses], 200);
    }

    public function modulePerCourses(Request $request, $courseId, $id){
        $request->query->add(['id' => $id, 'id' => $courseId]);

        $students = $request->validate([
            'id' => 'numeric|min:1|exists:students,id',
            'moduleId' => 'numeric|min:1|exists:students,id',
        ]);
        
        $modules = DB::SELECT("select m.id moduleId, sm.studentId, m.name module_name, sm.remarks, sm.status, 
                                (CASE WHEN sm.status = 0 THEN 'deleted' WHEN sm.status = 1 THEN 'active' WHEN sm.status = 2 THEN 'pending' WHEN sm.status = 3 THEN 'completed' END) as status_code, sm.updated_at
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

        // !empty($request->LI)? $links += ['li' => $request->LI] : '';
        // !empty($request->IG)? $links += ['ig' => $request->IG] : '';
        // !empty($request->FB)? $links += ['fb' => $request->FB] : '';
        // !empty($request->TG)? $links += ['tg' => $request->TG] : '';
        // !empty($request->WS)? $links += ['ws' => $request->WS] : '';

        ($request->has('LI'))? $links += ['li' => addslashes($request->LI)] : '';
        ($request->has('IG'))? $links += ['ig' => addslashes($request->IG)] : '';
        ($request->has('FB'))? $links += ['fb' => addslashes($request->FB)] : '';
        ($request->has('TG'))? $links += ['tg' => addslashes($request->TG)] : '';
        ($request->has('WS'))? $links += ['ws' => addslashes($request->WS)] : '';

                        // dd($links, $request->all());
                        
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
            'id' => 'numeric|min:1|exists:students,id',
        ]);
        
        // $student = Student::where('id', $id)->first();
        // $student = COLLECT(\DB::SELECT("SELECT s.*, u.email update_by FROM students s LEFT JOIN users u ON s.updated_by = u.id WHERE s.id = $id"))->first();
        $student = DB::TABLE("students as s")
                    ->leftJoin('users as u','s.updated_by','=','u.id')
                    ->where('s.id', '=', $id)
                    ->selectRaw("s.*, u.email update_by")
                    ->first();

        $student->links = Links::where('studentId', $student->id)->get();

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

    public function updateStudentModule(Request $request){

        $request->validate([
            'modules' => 'required|string',
        ]);
        
        $modules = json_decode($request->modules);
        
        foreach ($modules->modules as $key => $value) {
            
            if($value->status == "deleted"){
                $status = 0;
            }elseif($value->status == "pending"){
                $status = 2;
            }elseif($value->status == "completed"){
                $status = 3;
            }else{
                $status = 1;
            }
        
            $studentModule = StudentModule::where("studentId", $modules->student_id)->where("moduleId", $value->module_id)->first();
            
            if($value->remarks != $studentModule['remarks'] || $value->status != $studentModule['status']){
                
                $studentModule->update(
                                [ 
                                    'remarks' => $value->remarks,
                                    'status' => $status,
                                    'updated_at' => now()
                                ]
                                );
            }
                    
        }
        
        return response(["message" => "successfully updated student's module"], 200);
    }

    public function extendCourse(Request $request){
        
        $request->validate([
            'student_id' => 'required|numeric|min:1|exists:students,id',
            'course_id' => 'required|numeric|min:1|exists:courses,id',
            'starting_date' => 'required|date_format:Y-m-d H:i:s',
            'expiration_date' => 'required|date_format:Y-m-d H:i:s',
        ]);

        $studentModule = StudentCourse::where("studentId", $request->student_id)->where("courseId", $request->course_id)->first();
                
        $studentModule->update(
                        [ 
                            'starting' => $request->starting_date,
                            'expirationDate' => $request->expiration_date,
                            'updated_at' => now()
                        ]
                        );
                        
        return response(["message" => "successfully extend student's course"], 200);

    }

    public function addStudent(Request $request){

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|unique:students',
            'password' => 'required|confirmed|min:8',
        ]);

        // $link = Links::where('studentId', $id)->where('name', $key)->first();
        $student = DB::transaction(function() use ($request) {

                        $student = Student::create($request->only('phone', 'location', 'company', 'position', 'field') + 
                                                    [
                                                        'name' => $request->name,
                                                        'email' => $request->email,
                                                        'password' => Hash::make($request->password),
                                                        'updated_by' => auth('api')->user()->id
                                                    ]);
                        
                        Student::createLinks($student->id, $request->all());

                        $student->links = Links::where('studentId', $student->id)->get();
                        
                        return $student;

                    });

        // dd($request->all());

        return response(["Student" => $student], 200);

    }

    public function addStudentCourse(Request $request){
    
        $request->validate([
            'studentId' => 'required|numeric|exists:students,id',
            'courseId' => 'required|numeric|exists:courses,id',
            'starting_date' => 'required|date_format:Y-m-d H:i:s',
            'expiration_date' => 'required|date_format:Y-m-d H:i:s',
        ]);
        
        $studentCourse = Studentcourse::insertStudentCourse($request->all());

        // dd($request->all(), $studentCourse);
       
        if(!$studentCourse){
            return response(["message" => "record already exist"], 409);
        }
               
        return response(["message" => "successfully added student's course"], 200);

    }

    public function getPayment(Request $request, $id){
        $request->query->add(['id' => $id]);

        $request->validate([
            'id' => 'required|numeric|min:1|exists:students,id',
        ]);

        $payment = DB::SELECT("SELECT *, concat(first_name, ' ', last_name) name FROM payments where student_id = $id");

        foreach ($payment as $key => $value) {
            $value->courses = DB::SELECT("select c.id course_id, c.name course_name, pi.quantity course_quantity
                                    from payment_items pi
                                    left join courses c ON c.id = pi.product_id
                                    where pi.payment_id = $value->id");
        }


        // dd($request->all(), $id, $payment);
        return response(["payment" => $payment], 200);

    }
}
