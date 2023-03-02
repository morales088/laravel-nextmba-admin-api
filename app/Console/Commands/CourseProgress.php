<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Studentmodule;
use App\Models\Studentcourse;
use App\Models\Module;
use App\Models\Course;
use App\Models\Student;
use DB;

class CourseProgress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:complete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Complete all finished modules';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $DBtransaction = DB::transaction(function() {

            // check duplicate student modules
            $sms = DB::SELECT("select * 
                                from ( select *,
                                count(id) count,
                                GROUP_CONCAT(id ORDER BY id ASC),
                                SUBSTRING_INDEX(GROUP_CONCAT(id ORDER BY id DESC), ',', -COUNT(*) + 1) AS ids
                                from student_modules
                                where status <> 0
                                group by studentId, moduleId ) as sm where sm.count > 1");
            foreach ($sms as $smsKey => $sm) {
                $update = DB::update(DB::raw('UPDATE student_modules 
                                    SET status = 0, updated_at = "'.now().'"
                                    WHERE id in ("'.$sm->ids.'")'));
                // $this->line($sm->ids);
                // dd($sm, $update);
            }
            // dd($sms);




            $students = Student::where('status', 1)->get();

            foreach ($students as $studentKey => $student) {
                $studentCourses = Studentcourse::where('studentId', $student->id)->where('status', 1)->get();
                
                foreach ($studentCourses as $courseKey => $course) {

                    $modules = Module::where('courseId', $course->courseId)
                                        ->whereIn('broadcast_status', [3,4])
                                        ->where('status', 2)
                                        ->whereDate('start_date', '>=', $student->created_at)
                                        ->get();


                    foreach ($modules as $moduleKey => $module) {
                        $studentModule = Studentmodule::where('studentId', $student->id)
                                                        ->where('moduleId', $module->id)
                                                        ->where('status', '<>', 0)
                                                        ->get();
                            
                        if($studentModule->isEmpty()){
                            $newStudModule = new Studentmodule;
                            $newStudModule->studentId = $student->id;
                            $newStudModule->moduleId = $module->id;
                            $newStudModule->status = 3;
                            $newStudModule->save();

                            $this->line("Student Id '$student->id' - Module Id '$module->id'.");
                        }else{                       
                            foreach ($studentModule as $key => $value) {
                                $studModule = Studentmodule::find($value->id);
                                $studModule->status = 3;
                                $studModule->save();
                                // dd($studModule);
                                $this->line("Student Id '$student->id' - Module Id '$module->id'.");
                            }
                        }
                    }
                                                    
                }
                    
            }
            
        });

        $this->line("DONE.");
    }
}
