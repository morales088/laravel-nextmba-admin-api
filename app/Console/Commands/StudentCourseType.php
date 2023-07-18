<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Studentcourse;
use DB;

class StudentCourseType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'identify:course';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'identify students course type';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $DBtransaction = DB::transaction(function() { 
            $manualStudents = DB::SELECT("select * 
                                from payments
                                where payment_method = 'manual'");
                                
            foreach ($manualStudents as $key => $value) {
                $studentCourse = Studentcourse::where('studentId', $value->id)
                                                ->where('status', 1)
                                                ->update(['course_type' => 2]);

                $this->line("Student Id: $value->id");

            }

            // update student's course type to gifted
            $giftedStudents = DB::SELECT("select s.*
                                            from course_invitations ci
                                            left join students s ON ci.email = s.email
                                            where ci.status = 2");
                                
            foreach ($giftedStudents as $key => $value) {
                $studentCourse = Studentcourse::where('studentId', $value->id)
                                                ->where('status', 1)
                                                ->update(['course_type' => 3]);

                $this->line("Student Id: $value->id");

            }

        });

        $this->line("DONE.");
    }
}
