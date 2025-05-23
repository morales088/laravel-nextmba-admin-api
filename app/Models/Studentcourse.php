<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class Studentcourse extends Model
{
    use HasFactory;
    
    protected $guarded = ['id'];

    public static function insertStudentCourse($data, $course_type = 1){
        $student_id = $data['studentId'];
        $course_id = $data['courseId'];
        // $qty = (isset($data['qty'])? $data['qty'] : 1);
        $starting_date = isset($data['starting_date'])? $data['starting_date'] : now();
        $expiration_date = isset($data['expiration_date'])? $data['expiration_date'] : now()->addMonths(12);
        
        $checker = DB::SELECT("SELECT * FROM studentcourses where studentId = $student_id and courseId = $course_id and status <> 0");
        
        if(!empty($checker)){
            return false;
        }
        
        DB::transaction(function() use ($student_id, $course_id, $starting_date, $expiration_date, $course_type) {

            $Studentcourse = Studentcourse::create(
                                        [
                                            'studentId' => $student_id,
                                            'courseId' => $course_id,
                                            'course_type' => $course_type,
                                            'starting' => $starting_date,
                                            'expirationDate' => $expiration_date,
                                            // 'quantity' => --$qty,
                                            'quantity' => 0,
                                        ]);

            $modules = Module::Where('courseId', $course_id)->Where('status', '<>', 0)->get();

            foreach ($modules as $key => $value) {
                $array = [
                    'studentId' => $student_id,
                    'moduleId' => $value->id,
                ];

                // if($value->start_date < now()){
                //     $array['status'] = 3;
                // }
                
                Studentmodule::create($array);
            }

            return true;

        });

        return true;


    }

    public static function addAllCourse($student_id){
        $active_course = Studentcourse::where('studentId', $student_id)
                                        ->where('status', 1)
                                        ->pluck('courseId')
                                        ->toArray();
        
        // $active_course = implode(',', $active_course);

        $other_courses = Course::where('status', 1)
                                ->where('paid', 1)
                                // ->where('is_displayed', 1)
                                ->whereNotIn('id', $active_course)
                                ->get();

        // dd($active_course, $other_courses->toArray());

        $starting_date = now();
        $expiration_date = now()->addMonths(12);

        foreach ($other_courses as $key => $value) {
            $checkCourse =  Studentcourse::where('studentId', $student_id)
                                        ->where('courseId', $value['id'])
                                        ->where('status', true)
                                        ->first();
            if(empty($checkCourse)){
                $Studentcourse = Studentcourse::create(
                    [
                        'studentId' => $student_id,
                        'courseId' => $value['id'],
                        'starting' => $starting_date,
                        'expirationDate' => $expiration_date,
                        'quantity' => 0,
                    ]);
            }
        }
        return $other_courses;
        
    }

    public static function getPastModule($student_id, $course_id){
        $module_per_course = env('MODULE_PER_COURSE');

        $past_module = DB::TABLE("studentcourses as sc")
                                ->leftJoin("modules as m", "sc.courseId", "=", "m.courseId")
                                ->where("m.status", 2)
                                ->where("sc.status", 1)
                                ->whereIn("m.broadcast_status", [3,4])
                                ->where("sc.courseId", $course_id)
                                ->where("sc.studentId", $student_id)
                                ->whereRaw("date(m.start_date) >= date(sc.starting)")
                                ->select('sc.*',DB::RAW("TIMESTAMPDIFF(YEAR, sc.starting, sc.expirationDate) * $module_per_course AS module_per_course"), DB::RAW("Count(m.id) as past_module_count"))
                                ->first();
                                
        return $past_module;
    }
}
