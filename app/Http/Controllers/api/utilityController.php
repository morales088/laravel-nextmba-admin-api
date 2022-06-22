<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Studentmodule;
use DB;

class utilityController extends Controller
{
    public function studentModules(Request $request){

        // dd($request->all());
        DB::transaction(function() use ($request) {

            $modules = DB::SELECT("select m.*
                                    from courses c
                                    left join modules m ON c.id = m.courseId
                                    where c.status <> 0 order by c.id");

            $student_courses = DB::SELECT("select sc.*
                                            from students s
                                            right join studentcourses sc ON s.id = sc.studentId
                                            where s.status <> 0");
                                            
            foreach ($modules as $key => $value) {
                
                foreach ($student_courses as $key2 => $value2) {
                    
                    // $check_student_module = Studentmodule::where('studentId', '=', $value2->studentId)->where('moduleId', '=', $value->id)->get();
                    $check_student_module = DB::SELECT("SELECT * FROM student_modules where studentId = $value2->studentId and moduleId = $value->id");

                    if(empty($check_student_module)){

                        $array = [
                            'studentId' => $value2->studentId,
                            'moduleId' => $value->id,
                        ];
        
                        if($value->start_date < now()){
                            $array['status'] = 3;
                        }
                        
                        Studentmodule::create($array);
                    }

                }

            }
            
        },5);  // try 5 times
        
    }
}
