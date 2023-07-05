<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Studentcourse;
use DB;

class AdditionalAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'additional:access';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add additional lecture to all students';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {        
        $DBtransaction = DB::transaction(function() {
            $active_student = DB::SELECT("SELECT * FROM students where status = 1");
            
            $starting_date = now();
            $expiration_date = now()->addMonths(12);

            foreach ($active_student as $key => $value) {
                
                $additional_course = Studentcourse::where('studentId', $value->id)
                    ->where('status', 1)
                    ->where('courseId', 3)
                    ->get();
                    
                // dd($additional_course, $additional_course->isEmpty());
                if($additional_course->isEmpty()){
                    // dd($value->id);
                    $Studentcourse = Studentcourse::create(
                                    [
                                        'studentId' => $value->id,
                                        'courseId' => 3,
                                        'starting' => $starting_date,
                                        'expirationDate' => $expiration_date,
                                        'quantity' => 0,
                                    ]);
                    $this->line("Student id: $value->id");
                }

                
            }

            $this->line("DONE.");
            
        });
    }
}
