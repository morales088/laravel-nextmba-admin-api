<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Studentcourse;
use DB;

class ProAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pro:access';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add all course to all pro students';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        
        $DBtransaction = DB::transaction(function() {
            $pro_student = DB::SELECT("SELECT * FROM students where status = 1 and account_type = 3");

            foreach ($pro_student as $key => $value) {
                // dd($value);
                Studentcourse::addAllCourse($value->id);
            
                $this->line("Student id: $value->id");
                
            }

            $this->line("DONE.");
            
        });
    }
}
