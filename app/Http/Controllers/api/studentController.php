<?php

namespace App\Http\Controllers\api;

use DB;
use Mail;
use Validator;
use App\Models\Links;
use App\Models\Course;
use App\Models\Module;
use League\Csv\Writer;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Affiliate;
use Illuminate\Support\Str;
use App\Models\VideoLibrary;
use Illuminate\Http\Request;
use App\Models\Studentcourse;
use App\Models\Studentmodule;
use App\Models\Studentsetting;
use Illuminate\Validation\Rule;
use App\Mail\UpdateAccountEmail;
use App\Http\Controllers\Controller;
use App\Mail\AccountCredentialEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\StreamedResponse;

class studentController extends Controller
{

    public function index(Request $request) {
        
        $request->validate([
            'course_id' => 'array',
            'without_course_id' => 'array',
            'sort_column' => 'required|string',
            'sort_type' => 'required|string',
            'location' => 'string',
            'phone' => 'string',
            'company' => 'string',
            'position' => 'string',
            'status' => 'string|in:all,active,deactivated',
            'per_page' => 'numeric:min1'
        ]);

        // filters
        $queryFilter = [
            'course_id' => $request->course_id,
            'without_course_id' => $request->without_course_id,
            'location' => $request->location,
            'phone' => $request->phone,
            'company' => $request->company,
            'position' => $request->position,
            'status' => $request->status,
            'search' => $request->search,
            'sort_column' => $request->sort_column,
            'sort_type' => $request->sort_type,
            'per_page' => $request->per_page
        ];

        // get filtered students
        $students = Student::getStudents($queryFilter, true);

        // count all active students
        $totalStudents = Student::where('status', '<>', 0)->count();

        return response()->json([
            'students' => $students,
            'total_students' => $totalStudents,
        ], 200);
    }

    public function generateCSV(Request $request) {

        $batchSize = 1000;
        $totalCount = Student::getStudents($request->all())->count();
        $numBatches = ceil($totalCount / $batchSize);

        if ($totalCount == 0) {
            return response()->json(['message' => 'No students data found.'], 404);
        }

        // set CSV headers
        $csvHeaders = [
            'ID', 'Name', 'Email'
        ];

        $csv = Writer::createFromString('');
        $csv->setOutputBOM(Writer::BOM_UTF8);
        $csv->insertOne($csvHeaders);
    
        // loop through each batch and insert students' data into the CSV
        for ($batchNumber = 0; $batchNumber < $numBatches; $batchNumber++) {

            $offset = $batchNumber * $batchSize;
            // get filtered students for the current batch
            $students = Student::getStudents($request->all())->skip($offset)->take($batchSize);
    
            // insert each student's data into the CSV
            foreach ($students as $student) {

                $csv->insertOne([
                    $student->id,
                    $student->name,
                    $student->email
                ]);
            }
        }
    
        // set the response headers for CSV download
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="students.csv"',
        ];
     
        // output the CSV content to the browser as a downloadable file
        return response($csv->toString(), 200, $headers);
    }

    // old get students
    // public function index(Request $request){

    //     $login = $request->validate([
    //         'page' => 'required|numeric|min:1',
    //         'sort_column' => 'required|string',
    //         'sort_type' => 'required|string',
    //         'course' => 'string',
    //         'location' => 'string',
    //         'phone' => 'string',
    //         'company' => 'string',
    //         'position' => 'string',
    //         'interest' => 'string',
    //         'status' => [
    //                         Rule::in(['all', 'active', 'deactivated']),
    //                     ],
    //     ]);

    //     $query_filter = [];

    //     !empty($request->course)? $query_filter += ['course' => $request->course] : '';
    //     !empty($request->location)? $query_filter += ['location' => $request->location] : '';
    //     !empty($request->phone)? $query_filter += ['phone' => $request->phone] : '';
    //     !empty($request->company)? $query_filter += ['company' => $request->company] : '';
    //     !empty($request->position)? $query_filter += ['position' => $request->position] : '';
    //     !empty($request->interest)? $query_filter += ['interest' => $request->interest] : '';
    //     !empty($request->status)? $query_filter += ['status' => $request->status] : '';
    //     !empty($request->search)? $query_filter += ['search' => $request->search] : '';

    //     !empty($request->page)? $query_filter += ['page' => $request->page] : '';
    //     (!empty($request->sort_column) && !empty($request->sort_column) )? $query_filter += ['sort_column' => $request->sort_column, 'sort_type' => $request->sort_type] : '';

    //     // dd('asc' === 'ASC');
    
    //     $students = Student::getStudent($query_filter);

    //     // dd($students);
        
    //     foreach ($students as $key => $value) {

    //         $studentLinks = Student::getStudentLinks($value->id);
    //         $value->links = $studentLinks;
    //     }

    //     $total_students = Student::where('status', '<>', 0)->count();
    //     // dd($total_students);

    //     return response(["students" => $students, "total_students" => $total_students], 200);
    // }

    public function coursesByStudent(Request $request, $id){
        $module_per_course = env('MODULE_PER_COURSE');
        
        $request->query->add(['id' => $id]);

        $studentId = $request->validate([
            'id' => 'numeric|min:1|exists:students,id',
        ]);
        
        $totalModules = 0;
        $courses = DB::SELECT("select sc.studentId, c.id courseId, c.name, c.price course_price, sc.starting date_started, sc.expirationDate, sc.completed_modules, sc.completed_modules
                                from studentcourses sc
                                left join student_modules sm ON sm.id = sc.studentId
                                left join courses c ON c.id = sc.courseId
                                where sc.studentId = $id and sc.status <> 0");

        foreach ($courses as $key => $value) {
            $value->totalModules = ++$totalModules;

            $completedModules = $value->completed_modules;

            $modules = DB::SELECT("select sm.id, m.name module_name, sm.remarks, sm.status, 
                                    (CASE WHEN sm.status = 0 THEN 'deleted' WHEN sm.status = 1 THEN 'active' WHEN sm.status = 2 THEN 'pending' WHEN sm.status = 3 THEN 'completed' END) as status_code, sm.updated_at
                                    from student_modules sm
                                    left join modules m ON m.id = sm.moduleId
                                    where sm.status <> 0 and m.courseId = $value->courseId and sm.studentId = $id and m.start_date >= '$value->date_started'");
                                    
            foreach ($modules as $key2 => $value2) {
                if($value2->status == 3){ $completedModules++; }
            }
            
            $value->completedModules = $completedModules;
            $value->score_percentage = ($completedModules >= $module_per_course) ? 100 : round(($completedModules / $module_per_course) * 100, 2);
            $value->modules = $modules;
        }

        return response(["coursesPerStudent" => $courses], 200);
    }

    public function modulePerCourses(Request $request, $courseId, $id){
        $module_per_course = env('MODULE_PER_COURSE');

        $request->query->add(['id' => $id, 'courseId' => $courseId]);

        $students = $request->validate([
            'id' => 'numeric|min:1|exists:students,id',
            'courseId' => 'numeric|min:1|exists:courses,id',
        ]);

        $course = DB::SELECT("select c.*, sc.starting, sc.expirationDate, c.price course_price,
                                SUM(CASE WHEN sm.status = 1 THEN 1 ELSE 0 END) AS `incomple_modules`,
                                -- SUM(CASE WHEN sm.status = 3 THEN 1 ELSE 0 END) AS `complete_modules`,
                                count(sm.id) total_st_modules,
                                IF( (SUM(CASE WHEN sm.status = 3 THEN 1 ELSE 0 END) + sc.completed_modules)  >= count(sm.id), 100.00, ROUND( ( ( (SUM(CASE WHEN sm.status = 3 THEN 1 ELSE 0 END) + sc.completed_modules) / count(sm.id)) * 100 ), 0 )) score_percentage
                                from courses c
                                left join modules m ON m.courseId = c.id
                                left join student_modules sm ON m.id = sm.moduleId
                                left join studentcourses sc ON c.id = sc.courseId and sc.studentId = sm.studentId
                                where c.status <> 0 and m.status = 2 and sm.status <> 0 and sc.status <> 0 and m.pro_access = 0
                                and sm.studentId = $id and c.id = $courseId and sc.starting <= m.start_date");

        $course[0]->complete_modules = DB::TABLE("studentcourses as sc")
                            ->leftJoin("modules as m", "sc.courseId", "=", "m.courseId")
                            ->where("m.status", 2)
                            ->where("sc.status", 1)
                            ->whereIn("m.broadcast_status", [3,4])
                            ->where("sc.courseId", $courseId)
                            ->where("sc.studentId", $id)
                            ->whereRaw("date(m.start_date) >= date(sc.starting)")
                            ->count();
        // dd($course);
                
        // $modules = DB::SELECT("select m.id moduleId, sm.studentId, m.name module_name, sm.remarks, sm.status, 
        //                         (CASE WHEN sm.status = 0 THEN 'deleted' WHEN sm.status = 1 THEN 'active' WHEN sm.status = 2 THEN 'pending' WHEN sm.status = 3 THEN 'completed' END) as status_code, sm.updated_at
        //                         from student_modules sm
        //                         left join modules m ON m.id = sm.moduleId
        //                         where sm.status <> 0 and m.courseId = $courseId and sm.studentId = $id");  

        // $modules = DB::SELECT("select m.id moduleId, sc.studentId, m.name module_name, sm.remarks, sm.status, 
        //                         (CASE WHEN sm.status = 0 THEN 'deleted' WHEN sm.status = 1 THEN 'active' WHEN sm.status = 2 THEN 'pending' WHEN sm.status = 3 THEN 'completed' END) as status_code, 
        //                         sm.updated_at
        //                         from modules m
        //                         left join studentcourses sc ON m.courseId = sc.courseId
        //                         left join student_modules sm ON m.id = sm.moduleId and sm.studentId = sc.studentId
        //                         where sc.status <> 0 and sm.status <> 0 and m.pro_access = 0 and
        //                         sc.courseId = $courseId and sc.studentId = $id
        //                         and sc.starting <= m.start_date order by m.start_date");

        $modules = DB::SELECT("select m.id moduleId, sc.studentId, m.name module_name, sm.remarks, sm.status,
                                (CASE WHEN sm.status = 0 THEN 'deleted' WHEN sm.status = 1 THEN 'active' WHEN sm.status = 2 THEN 'pending' WHEN sm.status = 3 THEN 'completed' END) as status_code,
                                sm.updated_at
                                from modules m
                                left join studentcourses sc ON m.courseId = sc.courseId
                                left join student_modules sm ON m.id = sm.moduleId and sm.studentId = sc.studentId and sm.status <> 0
                                where sc.status <> 0 and m.pro_access = 0 and
                                sc.courseId = $courseId and sc.studentId = $id
                                and sc.starting <= m.start_date order by m.start_date");


        return response(["modulePerCourses" => $modules, 'course' => $course], 200);
    }

    public function updateStudent(Request $request, $id){

        $students = Student::find($id);
        $module_count = env('MODULE_PER_COURSE');
        
        if (isset($request->account_type)) {
            if ($request->account_type == 3) {
                $module_count = $module_count;
    
                VideoLibrary::studentLibraryAccess($id);
                VideoLibrary::studentProAccess($id);

            } elseif ($request->account_type == 2) {
                $module_count = $module_count;
            } else {
                $module_count = 2;
            }

            $request->query->add(['module_count' => $module_count]);
        }
        
        $students->update($request->only('name', 'email', 'phone', 'location', 'company', 'position', 'field', 'chat_moderator', 'chat_access', 'library_access', 'account_type', 'module_count', 'course_date', 'language') +
                        [ 'updated_at' => now()]
                        );

        $links = [];

        ($request->has('LI'))? $links += ['li' => addslashes($request->LI)] : '';
        ($request->has('IG'))? $links += ['ig' => addslashes($request->IG)] : '';
        ($request->has('FB'))? $links += ['fb' => addslashes($request->FB)] : '';
        ($request->has('TG'))? $links += ['tg' => addslashes($request->TG)] : '';
        ($request->has('WS'))? $links += ['ws' => addslashes($request->WS)] : '';

        $affiliateAccess = $request->affiliate_access ?? $students->affiliate_access;
        
        $existingAffiliate = Affiliate::where('student_id', $id)
            ->where('status','<>', 0)
            ->first();

        if ($affiliateAccess == 1) {
            $this->approveStudentAsAffiliate($id);
        } elseif ($affiliateAccess == 0 && $existingAffiliate) {
            $this->disapproveStudentAsAffiliate($id);
        } else {
            return response()->json(['message' => "Invalid Request."]);
        }
                        
        foreach ($links as $key => $value) {

            $link = Links::where('studentId', $id)->where('name', $key)->first();
            
            if ($link) {
                $link->update([ 
                    'link' => $value,
                    'updated_at' => now()
                ]);

            } else {
                Links::create($request->only('icon') + [
                    'studentId' => $id,
                    'name' => $key,
                    'link' => $value
                ]);
            }
        }
        
        $newStudentInfos =  Student::find($id);
        $newStudentLinks =  Links::where('studentId', $id)->get();
        $newStudentInfos->links = $newStudentLinks;

        return response(["student" => $newStudentInfos], 200);
    }

    public function approveStudentAsAffiliate($studentId) {
        
        $student = Student::find($studentId);

        if ($student) {
            $existingAffiliate = Affiliate::where('student_id', $studentId)
                ->whereIn('affiliate_status', [0,1])
                ->where('status', '<>', 0)
                ->first();

            if (!$existingAffiliate) {
                // update student affiliate access flag
                $student->affiliate_access = 1;
                $student->save();

                // create student affiliate
                $student->affiliate()->create([
                    'admin_id' => Auth::user()->id,
                    'affiliate_status' => 1,
                    'affiliate_code' => bin2hex(random_bytes(5)),
                    'remarks' => "Directly approved as affiliate by admin.",
                    'status' => 1,
                ]);

                return response()->json([
                    'message' => "Student has been approved as affiliate."
                ], 200);

            } elseif ($existingAffiliate) {
                $student->affiliate_access = 1;
                $student->save();

                $student->affiliate()->update([
                    'admin_id' => Auth::user()->id,
                    'affiliate_status' => 1,
                    'remarks' => "Directly updated by admin."
                ]);

                return response()->json([
                    'message' => "Student has been approved / updated as affiliate."
                ], 200);
            }
        }
        return response()->json(['message' => "Student not found."], 404);
    }

    public function disapproveStudentAsAffiliate($studentId) {

        $student = Student::find($studentId);

        if ($student) {
            $existingAffiliate = Affiliate::where('student_id', $studentId)
                ->whereIn('affiliate_status', [1])
                ->where('status', '<>', 0)
                ->first();

            if ($existingAffiliate) {
                $student->affiliate_access = 0; // update affiliate access
                $student->save();

                // update student affiliate back to pending
                $existingAffiliate->affiliate_status = 0; // pending
                $existingAffiliate->remarks = "Affiliate updated by admin.";
                $existingAffiliate->save(); 

                return response()->json([
                    'message' => "Student has been disapproved as affiliate."
                ], 200);

            } else {
                return response()->json([
                    'message' => "Student does not have a affiliate data."
                ], 200);
            }
        } 
        return response()->json(['message' => "Student not found."], 404);
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
        
        $students->update([ 
            'updated_by' => auth('api')->user()->id,
            'status' => $status,
        ]);

        return response(["message" => "successfully updated this student"], 200);

    }

    public function studentById(Request $request, $id){
        
        $request->query->add(['id' => $id]);

        $studentId = $request->validate([
            'id' => 'numeric|min:1|exists:students,id',
        ]);
        
        $student = DB::TABLE("students as s")
            ->leftJoin('users as u','s.updated_by','=','u.id')
            ->where('s.id', '=', $id)
            ->selectRaw("s.*, u.email update_by")
            ->first();

        $student->links = Links::where('studentId', $student->id)->get();

        return response()->json(["student" => $student], 200);
    }


    public function changePassword(Request $request, $id){

        $textPassword = Str::random(8);
        $hashedPassword = Hash::make($textPassword);

        $students = Student::find($id);
        
        $students->update([ 
            'password' => $hashedPassword,
            'updated_by' => auth('api')->user()->id,
        ]);

        return response(["newPassword" => $textPassword, "message" => "successfully updated this student"], 200);
        
    }

    public function updateStudentModule(Request $request){

        $request->validate([
            'student_id' => 'required|numeric|min:1|exists:students,id',
            'course_id' => 'required|numeric|min:1|exists:courses,id',
            'modules' => 'required|string',
            'completed_modules' => 'min:1',
        ]);
        
        $modules = json_decode($request->modules);
        // dd($modules);
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
        
            $studentModule = StudentModule::where("studentId", $modules->student_id)
                                    ->where("moduleId", $value->module_id)
                                    // ->where("status", '<>', 0)
                                    ->first();
            // dd($value, $studentModule);
            if($studentModule){
            // if($value->remarks != $studentModule['remarks'] || $value->status != $studentModule['status']){
                // dd(1);
                $studentModule->update(
                                [ 
                                    'remarks' => $value->remarks,
                                    'status' => $status,
                                    'updated_at' => now()
                                ]
                                );
            }else{
                // dd($modules->student_id, $value->module_id, $value->remarks, $status);
                $newStudentModule = new StudentModule;
                $newStudentModule->studentId = $modules->student_id;
                $newStudentModule->moduleId = $value->module_id;
                $newStudentModule->remarks = $value->remarks;
                $newStudentModule->status = $status;
                $newStudentModule->save();
            }
                    
        }
        
        if($request->completed_modules){
            $studentModule = StudentCourse::where("studentId", $request->student_id)->where("courseId", $request->course_id)->first();
                
            $studentModule->update($request->only('completed_modules') +
                            [ 
                                'updated_at' => now()
                            ]
                            );
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

        $request->query->add(['starting' => $request->starting_date]);
        $request->query->add(['expirationDate' => $request->expiration_date]);            

        // dd($request->all());

        $studentModule = StudentCourse::where("studentId", $request->student_id)
                        ->where("courseId", $request->course_id)
                        ->where("status", 1)
                        ->first();
                
        $studentModule->update($request->only('starting', 'expirationDate') +
                        [ 
                            'updated_at' => now()
                        ]
                        );
                        
        return response(["message" => "successfully extend student's course"], 200);

    }

    public function addStudent(Request $request){

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|unique:students',
            // 'password' => 'required|confirmed|min:8',
            'account_type' => [
                'numeric',
                Rule::in([1, 2, 3]),
            ]
        ]);

        // $link = Links::where('studentId', $id)->where('name', $key)->first();
        $student = DB::transaction(function() use ($request) {
            $module_count = env('MODULE_PER_COURSE');

            // generate random password
            $textPassword = Payment::generate_password();
            // dd($textPassword);

            if($request->account_type == 2 || $request->account_type == 3){
                $module_count = $module_count;
            }else{
                $module_count = 2;
            }
            
            $student = Student::create($request->only('phone', 'location', 'company', 'position', 'field', 'account_type') + 
                                        [
                                            'module_count' => $module_count,
                                            'name' => $request->name,
                                            'email' => $request->email,
                                            'password' => Hash::make($textPassword),
                                            'updated_by' => auth('api')->user()->id
                                        ]);
            
            Student::createLinks($student->id, $request->all());

            if($request->account_type == 3){
                VideoLibrary::studentLibraryAccess($student->id);
                VideoLibrary::studentProAccess($student->id);
            }
            
            
            // send user accout to email
            $user = [
                'email' => $request->email,
                'password' => $textPassword
            ];

            $recipients = [
                $request->email,
                env('ADMIN_EMAIL_ADDRESS')
            ];

            Mail::to($recipients)->send(new AccountCredentialEmail($user));

            $student->links = Links::where('studentId', $student->id)->get();
            
            return $student;

        });

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

        $payment = DB::SELECT("SELECT * FROM payments where student_id = $id");

        foreach ($payment as $key => $value) {
            $value->courses = DB::SELECT("select c.id course_id, c.name course_name, c.price course_price, pi.quantity course_quantity, pi.created_at
                                    from payment_items pi
                                    left join courses c ON c.id = pi.product_id
                                    where pi.payment_id = $value->id");
        }


        // dd($request->all(), $id, $payment);
        return response(["payment" => $payment], 200);

    }

    public function emailPassword(Request $request, $id){
        $request->query->add(['id' => $id]);

        $request->validate([
            'id' => 'required|numeric|min:1|exists:students,id',
            'password' => 'required|string'
        ]);

        $student = Student::find($id);

        // dd($student);

        // send user accout to email
        $user = [
            'email' => $student->email,
            'password' => $request->password
        ];

        Mail::to($student->email)->send(new UpdateAccountEmail($user));

        return response(["message" => "new password successfully sent to email"], 200);

    }

    public function removeStudentCourse(Request $request){
        
        $request->validate([
            'student_id' => 'required|numeric|min:1|exists:students,id',
            'course_id' => 'required|numeric|min:1|exists:courses,id',
        ]);

        $check = DB::SELECT("SELECT * FROM studentcourses where studentId = $request->student_id and courseId = $request->course_id and status <> 0");
        // dd($check[0]->id);
        if(empty($check)){
            return response(["message" => "No course found for this student.",], 422);
        }

        
        $student_course = Studentcourse::find($check[0]->id);
        $student_course->update(
                            [ 
                                'status' => 0,
                                'updated_at' => now()
                                ]
                            );
        return response(["message" => "course successfully removed to student"], 200);
    }

}
