<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Payment;
use App\Models\Student;
use DB;

class paymentController extends Controller
{
    public function createPayment(Request $request){
        $payment = $request->validate([
            'reference_id' => 'required|string',
            'email' => 'required|string',
            'phone' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'country' => 'required|string',
            'product' => 'required|string',
            'url' => 'required|string',
            'utm_source' => 'required|string',
            'utm_medium' => 'required|string',
            'utm_campaign' => 'required|string',
            'utm_content' => 'required|string',
        ]);
        // dd($request->all());

        // CHECK IF ACCOUNT ALREADY EXISTING, IF NOT CREATE ACCOUNT
        // $checker = DB::SELECT("SELECT * FROM students where email = " . $request->email);

        // CREATE PAYMENT
        $payment = Payment::create($request->only('reference_id') +
        [
            // 'reference_id ' => $request->reference_id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'country' => $request->country,
            'product' => $request->product,
            'amount' => $request->amount,
            'status' => "Unpaid",
            'url' => $request->url,
            'utm_source' => $request->utm_source,
            'utm_medium' => $request->utm_medium,
            'utm_campaign' => $request->utm_campaign,
            'utm_content' => $request->utm_content
        ]);

        // $paymentItems = [];
        // if(str_contains($request->product, "Bundle")) {
        //     $course1 = [1, 1];
        //     array_push($paymentItems);
        //     $course2 = [2, 1];
        //     array_push($paymentItems);
        // } else if(str_contains($request->product, "Marketing")) {
        //     $qty = 1;
        //     if(str_contains($request->product, "10")) $qty = 20;
        //     else if(str_contains($request->product, "5")) $qty = 10;
        //     else if(str_contains($request->product, "3")) $qty = 6;
        //     $item = [1, $qty];
        //     array_push($paymentItems);
        // } else if(str_contains($request->product, "Executive")) {
        //     $qty = 1;
        //     if(str_contains($request->product, "10")) $qty = 20;
        //     else if(str_contains($request->product, "5")) $qty = 10;
        //     else if(str_contains($request->product, "3")) $qty = 6; 
        //     $item = [2, $qty];
        //     array_push($paymentItems);
        // }
        

        // dd($paymentItems, str_contains($request->product, "Marketing"));
        return response(["message" => "successfully created pending payments", "payment" => $payment], 200);
    }


    public function completePayment(Request $request){

        $payment = $request->validate([
            'reference_id' => 'required|string',
        ]);

        $studentinfo = DB::transaction(function() use ($request) {
            // // create user/student 
            $studentChecker = DB::SELECT("select *
                                            from students s
                                            left join payments p ON p.email = s.email
                                            where p.reference_id = '$request->reference_id' and s.status <> 0");

            $paymentInfo = COLLECT(\DB::SELECT("SELECT * FROM payments where reference_id = '$request->reference_id'"))->first();
                                            
            $password = Payment::generate_password();
            if(empty($studentChecker)){
                // CREATE NEW ACCOUNT
                    
                    $student = Student::create($request->only('phone', 'location', 'company', 'position', 'field') + 
                        [
                            'name' => $paymentInfo->first_name . ' ' . $paymentInfo->last_name,
                            'email' => $paymentInfo->email,
                            'password' => Hash::make($password),
                            'updated_at' => now()
                        ]);

                    // return $student;
            }else{
                $student = COLLECT(\DB::SELECT("select s.*
                                        from students s
                                        left join payments p ON p.email = s.email
                                        where p.reference_id = '$request->reference_id' and s.status <> 0"))->first();
            }

            $productInfo = COLLECT(\DB::SELECT("SELECT * FROM courses c where name like '%$paymentInfo->product%'"))->first();
            
            // insert data to payment_items
            DB::table('payment_items')->insert([
                [
                    'payment_id' => $paymentInfo->id, 
                    'product_id' => $productInfo->id, 
                    'quantity' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
            ]);

            // $Studentcourse = Studentcourse::create(
            //     [
            //         'studentId' => $request->studentId,
            //         'courseId' => $request->courseId,
            //         'starting' => $request->starting_date,
            //         'expirationDate' => $request->expiration_date,
            //     ]);

            // $modules = Module::Where('courseId', $request->courseId)->get();

            // foreach ($modules as $key => $value) {

            //     Studentmodule::create(
            //     [
            //     'studentId' => $request->studentId,
            //     'moduleId' => $value->id,
            //     ]);
            // }

            // dd($productInfo, $paymentInfo, $payment);

            // UPDATE PAYMENT
            $payment = Payment::find($paymentInfo->id);

            $payment->update(
                [ 
                    'student_id' => $student->id,
                    'status' => "Paid",
                ]
            );

        });
        

      
    }

    // function generate_password($length = 20){
    //     $chars =  'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
      
    //     $str = '';
    //     $max = strlen($chars) - 1;
      
    //     for ($i=0; $i < $length; $i++)
    //       $str .= $chars[random_int(0, $max)];
      
    //     return $str;
    //   }


    public function sendEmailAndPassword(Request $request, $email, $password)
    {
        Mail::send('email.send-account', ['email' => $email, 'user' => $password], function ($m) use ($user) {
            $m->from('service@next.university', 'NEXT University');
 
            $m->to($user->email, $user->name)->subject('NEXT University Account');
        });
    }
}
