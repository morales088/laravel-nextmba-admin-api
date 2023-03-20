<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Payment;
use DB;

class LibraryToPro extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'library:pro';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $DBtransaction = DB::transaction(function() {
            $students = DB::SELECT("SELECT * FROM students where pro_access = 1 order by id");

            foreach ($students as $key => $value) {
                $student = Student::find($value->id);

                $pro_check = Payment::where('student_id', $value->id)
                                        ->where('product', 'like', '%NEXT MBA PRO Account%')
                                        ->get();

                // dd($pro_check->isEmpty());
                // if($value->id == 4899){
                //     dd($pro_check, $pro_check->isEmpty());
                // }

                if($pro_check->isEmpty()){
                    $student->pro_access = 0;
                }else{
                    $student->pro_access = 1;
                }

                $student->library_access = 1;
                $student->save();

                $this->line("Student Id: $value->id");

            }

        });

        $this->line("DONE.");
    }
}
