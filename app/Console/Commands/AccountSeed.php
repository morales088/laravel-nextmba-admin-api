<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Payment;
use App\Models\Studentcourse;
use Mail;
use App\Mail\AccountCredentialEmail;
use DB;

class AccountSeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insert:account';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'seed account from csv';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // return 0;
        $path = base_path().'/public/csv/account.csv';
        // dd(true);
        if(file_exists($path)){
            $file = new \SplfileObject($path);
            $file->setFlags(\SplfileObject::READ_CSV);

            $account = DB::transaction(function() use ($file) {
                
                foreach ($file as $key => $value) {
                    
                    // check if value i null

                    if(!empty($value[0])){
                        list($email, $name, $status, $date_created, $last_login, $courses) = $value;

                        
                        $date_created = date('Y-m-d H:i:s', strtotime($date_created));
                        $expiration_date = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($date_created)));
                        // dd($email, $name, $status, $date_created, $last_login, $courses, explode(",", $courses));
                        dd($date_created, $expiration_date);
                        // check duplicate on db
                        $check = DB::SELECT("SELECT * FROM students where email = '$email'");

                        // dd($check, $status, empty($check), $status == "Active", empty($check) && $status == "Active");
                        
                        // dd(empty($check) && $status == "Active", empty($check), $status == "Active");
                        // check if active
                        if(empty($check) && $status == "Active"){
                            
                            // create student
                            $password = Payment::generate_password();
                            
                            $student = Student::create(
                            [
                                'name' => $name,
                                'email' => $email,
                                'password' => $password,
                                'created_at' => $date_created,
                            ]);
                            

                            // send account credentials to email
                            // $user = [
                            //     'email' => $email,
                            //     'password' => $password
                            // ];
                            // Mail::to($email)->send(new AccountCredentialEmail($user));

                            // if $courses has "Course" word str_contains($courses, "Course")
                            if(str_contains($courses, "Course")){
                                $student_courses = explode(",", $courses);
                                
                                // dd($student_courses, $date_created, $expiration_date);

                                foreach ($student_courses as $key1 => $value1) {
                                    
                                    if(str_contains($value1, "Marketing")){
                                        //insert marketing course to student
                                        $info = ['studentId' => $student->id, 'courseId' => 1, 'qty' => 1, 'starting_date' => $date_created, 'expiration_date' => $expiration_date];
                                        Studentcourse::insertStudentCourse($info);
                                    }

                                    if(str_contains($value1, "Executive")){
                                        //insert Executive course to student
                                        $info = ['studentId' => $student->id, 'courseId' => 2, 'qty' => 1, 'starting_date' => $date_created, 'expiration_date' => $expiration_date];
                                        Studentcourse::insertStudentCourse($info);
                                    }
                                }
                            }
                                
                        }
                    }
                }
            });
        }

    }
}
