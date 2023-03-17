<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use DB;

class StudentCourseDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:student_course_date';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

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
                $student = Student::find($value->id);
                // dd($student);
                $student->course_date = $value->created_at;
                $student->save();

                $this->line("Student Id: $value->id");

            }

        });

        $this->line("DONE.");
    }
}
