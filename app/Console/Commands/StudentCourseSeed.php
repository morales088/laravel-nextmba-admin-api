<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Studentcourse;
use DB;

class StudentCourseSeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transfer:studentCourse';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'transfer student course to new course';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        
        $DBtransaction = DB::transaction(function() {
            $students = DB::SELECT("SELECT * FROM students order by id");

            foreach ($students as $key => $value) {
                $student_courses = DB::SELECT("SELECT * FROM studentcourses where status <> 0 and studentId = $value->id");
                $existing_course = DB::SELECT("SELECT * FROM studentcourses where status <> 0 and courseId = 3");

                $starting_date = "2023-01-01 00:00:00";
                $expiration_date = "2023-12-31 00:00:00";
                
                // dd($value, $student_courses, $existing_course, empty($existing_course));
                // dd($starting_date, $expiration_date);
                // dd(!empty($student_courses), !empty($existing_course));

                if(!empty($student_courses) && !empty($existing_course)){

                    $studentCourse = new Studentcourse;
                    $studentCourse->studentId = $value->id;
                    $studentCourse->courseId = 3;
    
                    $studentCourse->starting = $starting_date;
                    $studentCourse->expirationDate = $expiration_date;
                    $studentCourse->quantity = 0;
    
                    $studentCourse->save();

                }
                
                $this->line("student ID : ".$value->id);
            }

            // transfer modules to new course
            $modules = DB::SELECT("UPDATE modules m
                                    SET m.courseId = 3
                                    where m.courseId in (1,2)");

            // give library access
            $library_access = DB::SELECT("UPDATE students s 
                                            SET 
                                                s.library_access = 1
                                            WHERE
                                                year(s.created_at) <= '2022'");
            // dd($students);
        });

        $this->line("DONE.");
        
    }
}
