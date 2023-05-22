<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Studentcourse;
use App\Models\Payment;
use App\Models\Courseinvitation;
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

            // transfer student access from id 3 to id 1&2
            $student_courses = DB::SELECT("SELECT * FROM studentcourses where courseId = 3");

            foreach ($student_courses as $key => $value) {

                $check_course_1 = DB::SELECT("SELECT * FROM studentcourses where studentId = $value->studentId and courseId = 1 and status = 1");
                $check_course_2 = DB::SELECT("SELECT * FROM studentcourses where studentId = $value->studentId and courseId = 2 and status = 1");
                
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

                $this->line("Student Id: $value->studentId");
            }
            

            $student_gifts = DB::SELECT("SELECT * FROM course_invitations WHERE course_id = 3");

            foreach ($student_gifts as $key => $value) {
                $check_course_1 = DB::SELECT("SELECT * FROM course_invitations ci WHERE email = '$value->email' and course_id = 1");
                $check_course_2 = DB::SELECT("SELECT * FROM course_invitations ci WHERE email = '$value->email' and course_id = 2");

                    if(empty($check_course_1)){

                        $studentGift = new Courseinvitation;
                        $studentGift->from_student_id = $value->from_student_id;
                        $studentGift->from_payment_id = $value->from_payment_id;
                        $studentGift->course_id = 1;
                        $studentGift->email = $value->email;
                        $studentGift->status = 2;
        
                        $studentGift->save();
                    }

                    if(empty($check_course_2)){

                        $studentGift = new Courseinvitation;
                        $studentGift->from_student_id = $value->from_student_id;
                        $studentGift->from_payment_id = $value->from_payment_id;
                        $studentGift->course_id = 2;
                        $studentGift->email = $value->email;
                        $studentGift->status = 2;
        
                        $studentGift->save();
                    }

                $this->line("Student email: $value->email");
            }

        });

        $this->line("DONE.");
    }
}
