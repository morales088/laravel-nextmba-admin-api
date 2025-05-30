<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Payment;
use App\Models\Studentcourse;
use Illuminate\Support\Facades\Hash;
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
        // $check = filter_var('johnneilmorales @gmail.com', FILTER_VALIDATE_EMAIL);
        // dd($check, now());
        // return 0;
        $path = base_path().'/public/csv/account.csv';
        // dd(true);
        if(file_exists($path)){
            $file = new \SplfileObject($path);
            $file->setFlags(\SplfileObject::READ_CSV);

            // force to seek to last line, won't raise error
            $file->seek($file->getSize());
            $linesTotal = $file->key();
            
            // dd($linesTotal);
            // $account = DB::transaction(function() use ($file) {
                $error_email = "";
                foreach ($file as $key => $value) {
                    $lineCount = ++$key."/".$linesTotal;
                    // check if value i null

                    if(!empty($value[0])){
                        list($email, $name, $status, $date_created, $last_login, $courses) = $value;
                        // dd($email, $name, $status, $date_created, $last_login, $courses, $linesTotal, ++$key);
                        // dd(str_contains($courses, "Course"), str_contains($courses, "CEO") || str_contains($courses, "CMO"));
                        $check = filter_var($email, FILTER_VALIDATE_EMAIL);
                        if($check){

                            // dd($date_created);
                            // $date_created = str_replace('/', '-', $date_created);
                            $date_created = date('Y-m-d H:i:s', strtotime($date_created));
                            $expiration_date = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($date_created)));
                            // dd($date_created, $expiration_date);
                            // check duplicate on db
                            $check = DB::SELECT("SELECT * FROM students where email = '$email'");
                            
                            // check if active
                            if(empty($check) && $status == "Active"){
                                
                                // create student
                                $password = Payment::generate_password();
                                
                                $student = Student::create(
                                [
                                    'name' => $name,
                                    'email' => $email,
                                    'password' => Hash::make($password),
                                    'created_at' => $date_created,
                                ]);
                                
    
                                // send account credentials to email
                                $user = [
                                    'email' => $email,
                                    'password' => $password
                                ];
                                Mail::to($email)->send(new AccountCredentialEmail($user));
    
                                // if $courses has "Course" word str_contains($courses, "Course")
                                // if(str_contains($courses, "Course")){
                                if(str_contains($courses, "CEO") || str_contains($courses, "CMO")){
                                    // $student_courses = explode(",", $courses);
                                    $student_courses = $courses;
                                    
                                    // dd($student_courses, $date_created, $expiration_date);
    
                                    // foreach ($student_courses as $key1 => $value1) {
                                        
                                        if(str_contains($student_courses, "CMO")){
                                            //insert marketing course to student
                                            $info = ['studentId' => $student->id, 'courseId' => 1, 'qty' => 1, 'starting_date' => $date_created, 'expiration_date' => $expiration_date];
                                            Studentcourse::insertStudentCourse($info);
                                        }
    
                                        if(str_contains($student_courses, "CEO")){
                                            //insert Executive course to student
                                            $info = ['studentId' => $student->id, 'courseId' => 2, 'qty' => 1, 'starting_date' => $date_created, 'expiration_date' => $expiration_date];
                                            Studentcourse::insertStudentCourse($info);
                                        }
                                    // }
                                }
                                $this->line($lineCount);
  
                            }else{
                                $error_email .= $email." (existing account)\n";
                                $this->error($lineCount." Existing account! - $email");
                            }

                        }else{
                            $error_email .= $email."\n";
                            $this->error($lineCount." Invalid Email! - $email");
                        }
                    }
                }

                if($error_email){

                    $txt_file = fopen("account_seed.txt", "a");
                    fwrite($txt_file, now()."\n");
                    fwrite($txt_file, $error_email);
                    fclose($txt_file);
                }
                // dd($error_email);
            // });
        }

    }
}
