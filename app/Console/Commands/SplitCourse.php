<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Studentcourse;
use App\Models\Payment;
use DB;

class SplitCourse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'split:course';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'course access depending on student course';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $DBtransaction = DB::transaction(function() {

            $students = DB::SELECT("SELECT * FROM studentcourses where courseId = 3");

            foreach ($students as $key => $value) {

                $check_course_1 = DB::SELECT("SELECT * FROM studentcourses where studentId = $value->studentId and courseId = 1 and status = 1");
                $check_course_2 = DB::SELECT("SELECT * FROM studentcourses where studentId = $value->studentId and courseId = 2 and status = 1");
                
                // dd($value);

                if(empty($check_course_1)){

                    $studentCourse = new Studentcourse;
                    $studentCourse->studentId = $value->studentId;
                    $studentCourse->courseId = 1;
                    $studentCourse->starting = $value->starting;
                    $studentCourse->expirationDate = $value->expirationDate;
                    $studentCourse->quantity = 0;
    
                    $studentCourse->save();
                }

                if(empty($check_course_2)){

                    $studentCourse = new Studentcourse;
                    $studentCourse->studentId = $value->studentId;
                    $studentCourse->courseId = 2;
                    $studentCourse->starting = $value->starting;
                    $studentCourse->expirationDate = $value->expirationDate;
                    $studentCourse->quantity = 0;
    
                    $studentCourse->save();
                }
                
                // dd($value, empty($check_course_1), empty($check_course_2));

                $this->line("Student Id: $value->studentId");
            }
            
            // dd($students);
        });

        $this->line("DONE.");
    }
}
