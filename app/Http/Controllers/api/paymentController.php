<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Mail\AccountCredentialEmail;
use App\Mail\PaymentConfirmationEmail;
use App\Models\User;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Studentcourse;
use App\Models\VideoLibrary;
use Validator;
use Mail;
use DB;

class paymentController extends Controller
{
    public function index(Request $request){

        $request->validate([
            'page' => 'numeric|min:1',
            'date_created' => 'string',
            'utm_source' => 'string',
            'utm_medium' => 'string',
            'utm_campaign' => 'string',
            'utm_content' => 'string',
            'name' => 'string',
            'country' => 'string',
            'product_name' => 'string',
            'status' => 'string',
        ]);

        $query_filter = [];

        !empty($request->date_created)? $query_filter += ['date_created' => $request->date_created] : '';
        !empty($request->utm_source)? $query_filter += ['utm_source' => $request->utm_source] : '';
        !empty($request->utm_medium)? $query_filter += ['utm_medium' => $request->utm_medium] : '';
        !empty($request->utm_campaign)? $query_filter += ['utm_campaign' => $request->utm_campaign] : '';
        !empty($request->utm_content)? $query_filter += ['utm_content' => $request->utm_content] : '';
        !empty($request->name)? $query_filter += ['name' => $request->name] : '';
        !empty($request->country)? $query_filter += ['country' => $request->country] : '';
        !empty($request->product_name)? $query_filter += ['product_name' => $request->product_name] : '';
        !empty($request->status)? $query_filter += ['status' => $request->status] : '';
        !empty($request->search)? $query_filter += ['search' => $request->search] : '';
        
        !empty($request->page)? $query_filter += ['page' => $request->page] : '';
        (!empty($request->sort_column) && !empty($request->sort_type) )? $query_filter += ['sort_column' => $request->sort_column, 'sort_type' => $request->sort_type] : '';
        // dd($query_filter);
        
        $payments = Payment::getPayment($query_filter);

        return response(["payments" => $payments], 200);

    }

    public function createPayment(Request $request){
        $payment = $request->validate([
            'reference_id' => 'required|string',
            // 'hitpay_id' => 'required|string',
            'email' => 'required|string',
            'phone' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'country' => 'required|string',
            'product' => 'required|string',
            'url' => 'required|string',
            'amount' => 'string',
            // 'utm_source' => 'required|string',
            // 'utm_medium' => 'required|string',
            // 'utm_campaign' => 'required|string',
            // 'utm_content' => 'required|string',
        ]);
        // dd($request->all());        
                
        $request->query->add(['price' => $request->amount]);

        // CHECK IF ACCOUNT ALREADY EXISTING, IF NOT CREATE ACCOUNT
        // $checker = DB::SELECT("SELECT * FROM students where email = " . $request->email);

        // CREATE PAYMENT
        $payment = Payment::create($request->only('hitpay_id', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'price') +
        [
            'reference_id ' => $request->reference_id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'country' => $request->country,
            'product' => $request->product,
            'amount' => $request->amount,
            'status' => "Unpaid",
            'url' => $request->url,
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

        $payment = DB::transaction(function() use ($request) {

            // // create user/student 
            $studentChecker = DB::SELECT("select *
                                            from students s
                                            left join payments p ON p.email = s.email
                                            where p.reference_id = '$request->reference_id' and s.status <> 0");

            $paymentInfo = COLLECT(\DB::SELECT("SELECT * FROM payments where reference_id = '$request->reference_id'"))->first();
                                  
            $password = Payment::generate_password();
            $email = "";
            $name = "";
            if(empty($studentChecker)){
                // CREATE NEW ACCOUNT
                
                    $student = Student::create($request->only('phone', 'location', 'company', 'position', 'field') + 
                        [
                            'name' => $paymentInfo->first_name . ' ' . $paymentInfo->last_name,
                            'email' => $paymentInfo->email,
                            'password' => Hash::make($password),
                            'updated_at' => now()
                        ]);

                    $name = $student->name;
                    $email = $student->email;

                    // return $student;
            }else{
                $student = COLLECT(\DB::SELECT("select s.*
                                        from students s
                                        left join payments p ON p.email = s.email
                                        where p.reference_id = '$request->reference_id' and s.status <> 0"))->first();

                $email = $student->email;
            }
            $productInfo = COLLECT(\DB::SELECT("SELECT * FROM courses c where name like '%$paymentInfo->product%'"))->first();
            // end

            // insert data to payment_items
            $paymentItems = [];
            if(str_contains($request->product, "executive") && str_contains($request->product, "technology")) {
                $course1 = ['studentId' => $student->id, 'courseId' => 2, 'qty' => 1];
                array_push($paymentItems, $course1);
                $course2 = ['studentId' => $student->id, 'courseId' => 3, 'qty' => 1];
                array_push($paymentItems, $course2);
            } else if(str_contains($paymentInfo->product, "marketing")) {
                $qty = 1;
                if(str_contains($paymentInfo->product, "20")) $qty = 20;
                else if(str_contains($paymentInfo->product, "10")) $qty = 10;
                else if(str_contains($paymentInfo->product, "6")) $qty = 6;
                $item = ['studentId' => $student->id, 'courseId' => 1, 'qty' => $qty];
                array_push($paymentItems, $item);
            } else if(str_contains($paymentInfo->product, "executive")) {
                $qty = 1;
                if(str_contains($paymentInfo->product, "20")) $qty = 20;
                else if(str_contains($paymentInfo->product, "10")) $qty = 10;
                else if(str_contains($paymentInfo->product, "6")) $qty = 6; 
                $item = ['studentId' => $student->id, 'courseId' => 2, 'qty' => $qty];
                array_push($paymentItems, $item);
            } else if(str_contains($paymentInfo->product, "technology")) {
                $qty = 1;
                if(str_contains($paymentInfo->product, "20")) $qty = 20;
                else if(str_contains($paymentInfo->product, "10")) $qty = 10;
                else if(str_contains($paymentInfo->product, "6")) $qty = 6; 
                $item = ['studentId' => $student->id, 'courseId' => 3, 'qty' => $qty];
                array_push($paymentItems, $item);
            }

            $insertPaymentItems = Payment::insertPaymentItems($paymentInfo->id, $paymentItems);
            //end
            
            // registrer student course
            foreach ($paymentItems as $key => $value) {
                Studentcourse::insertStudentCourse($value);
            }
            // end

            // UPDATE PAYMENT
            $payment = Payment::find($paymentInfo->id);

            $payment->update(
                [ 
                    'student_id' => $student->id,
                    'status' => "Paid",
                ]
            );

            if(empty($studentChecker)){
                $user = [
                    'email' => $email,
                    'password' => $password
                ];
                Mail::to($email)->send(new AccountCredentialEmail($user));
            }

            $user = [
                'email' => $email,
                'date' => now()
            ];
            Mail::to("service@next.university")->send(new PaymentConfirmationEmail($user));

            //emd
            return $paymentInfo;
        });
        
        return response(["message" => "success", "payment" => $payment], 200);
      
    }

    public function getPayment(Request $request, $id){
        $request->query->add(['id' => $id]);

        $request->validate([
            'id' => 'required|numeric|min:1|exists:payments,id',
        ]);

        $payment = COLLECT(\DB::SELECT("SELECT * FROM payments where id = $id"))->first();

        $payment->courses = DB::SELECT("select c.id course_id, c.name course_name, pi.quantity course_quantity
                                from payment_items pi
                                left join courses c ON c.id = pi.product_id
                                where pi.payment_id = $payment->id");

        // dd($request->all(), $id, $payment);
        return response(["payment" => $payment], 200);
    }

    public function refund(Request $request){
        // $userId = auth('api')->user()->id;
        $api_key = env('HITPAY_API_TOKEN');
        $api_link = env('HITPAY_API_LINK');

        $request->validate([
            'payment_id' => 'required|numeric|min:1|exists:payments,id',
        ]);

        $payment_info = collect(\DB::SELECT("SELECT * FROM payments where id = $request->payment_id"))->first();
        
        $response = Http::asForm()->withHeaders([
            'X-BUSINESS-API-KEY' => $api_key,
            'X-Requested-With' => 'XMLHttpRequest',
            'Content-Type' => 'application/x-www-form-urlencoded'
        ])->post($api_link.'/refund', [
            'amount' => $payment_info->price,
            'payment_id' => $payment_info->hitpay_id,
        ]);

        // dd($response, $response->serverError(), $response->successful(), $response->failed());
        
        if($response->successful()){

            DB::table('payments')
                        ->where('id', $payment_info->id)
                        ->update(['status' => 'Refunded', 'updated_at' => now()]);
            
            $payment_items = DB::SELECT("select * from payment_items where payment_id = $payment_info->id");

            foreach ($payment_items as $key => $value) {
                
                DB::table('studentcourses')
                ->where('studentId', $payment_info->student_id)
                ->where('courseId', $value->product_id)
                ->update(['status' => 0, 'updated_at' => now()]);
            }
            
            return response(["message" => "successfully refunded this course ($payment_info->product)"], 200);
        }

        return response()->json(["message" => "transaction failed"], 422);

    }

    public function updatePayment(Request $request, $id){
        $request->query->add(['id' => $id]);
        
        $request->validate([
            'id' => 'required|numeric|min:1|exists:payments,id',
            'status' => [
                        'string',
                        Rule::in(['unpaid', 'paid', 'refund']),
                    ],
        ]);

        $payment = Payment::find($id);
        
        $payment->update($request->only('remarks', 'status') + 
                    [ 
                        // 'status' => $request->status,
                        'updated_at' => now()
                    ]
                    );

        return response(["payment" => $payment], 200);
        
    }

    public function payment(Request $request){
        
        $validation = [
                    // 'reference_id' => 'string',
                    'email' => 'required|string',
                    // 'phone' => 'string',
                    // 'full_name' => 'string',
                    // 'country' => 'string',
                    'product' => 'required|string',
                    // 'url' => 'string',
                    'amount' => 'required|string',
                    'paid' => [
                                'string',
                                Rule::in(['true', 'false']),
                            ],
                    'manual_payment' => [
                                'string',
                                Rule::in(['true']),
                            ],
                ];
        if($request->manual_payment == true){
            // $validation['studentId'] = 'required|numeric|min:1|exists:students,id';
            $validation['courseId'] = 'required|numeric|min:1|exists:courses,id';
            $validation['course_qty'] = 'required|numeric|min:1';

            $request->query->add(['payment_method' => 'Manual']);
        }else{
            $validation['reference_id'] = 'string';
            $validation['phone'] = 'string';
            $validation['full_name'] = 'string';
            $validation['country'] = 'string';
            $validation['country'] = 'string';

            $request->query->add(['name' => $request->full_name]);
        }
        
        $payment = $request->validate($validation);

        $payment = DB::transaction(function() use ($request) {

            $courses = strtolower($request->product);
            $request->query->add(['price' => $request->amount]);
            $status = $request->paid == "true" ? "Paid" : "Unpaid" ; 

            if( isset($request->affiliate_code) ){
                $from_student = DB::table('partnerships')
                                        ->where(DB::raw('BINARY `affiliate_code`'), '=', $request->affiliate_code)
                                        ->where('status', 1)
                                        ->first();

                $from_student_id = isset($from_student->student_id) ? $from_student->student_id : 0;
                $request->query->add(['from_student_id' => $from_student_id]);

                if($from_student_id > 0){
                    $affiliate_count = DB::table('payments')
                                        ->where('from_student_id', '=', $from_student_id)
                                        ->where('status', 'Paid')
                                        ->count();
                    ++$affiliate_count;
                             
                    $partnerAffiliate_count = env('partnerAffiliate_count');
                    $proAffiliate_count = env('proAffiliate_count');
                    
                    if($affiliate_count >= $proAffiliate_count){
                        $affiliate_percentage = env('proCommissionPercent');
                    }elseif($affiliate_count >= $partnerAffiliate_count){
                        $affiliate_percentage = env('partnerCommissionPercent');
                        VideoLibrary::studentLibraryAccess($from_student_id);
                    }else{
                        $affiliate_percentage = env('beginnerCommissionPercent');
                    }

                    $request->query->add(['commission_percentage' => $affiliate_percentage]);
                    // dd($affiliate_percentage, $request->all(), $affiliate_count);
                    // if($affiliate_count >= 5){
                        $percentage = DB::table('partnerships')
                                        ->where("student_id", $from_student_id)
                                        ->update(["percentage" => $affiliate_percentage]);
                    // }
                    
                }
            }

            // dd($request->all());
            
            // CREATE PAYMENT
            $payment = Payment::create($request->only('name', 'reference_id', 'hitpay_id', 'quantity', 'phone', 'payment_method', 'product', 'country', 'url',
                                                    'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'affiliate_code', 'commission_percentage', 'from_student_id') +
            [
                // 'name' => $request->full_name,
                'email' => $request->email,
                'price' => $request->price,
                'status' => $status
            ]);

            $paymentId = $payment->id;

            if($request->paid){
                // // create user/student 
                $studentChecker = DB::SELECT("select *
                from students s where s.email = '$request->email'");

                $password = Payment::generate_password();
                // $email = "";
                // $name = "";

                if(empty($studentChecker)){
                    // CREATE NEW ACCOUNT
                    
                    $student = Student::create($request->only('name', 'phone', 'location', 'company', 'position', 'field') + 
                        [
                            // 'name' => $request->full_name,
                            'email' => $request->email,
                            'password' => Hash::make($password),
                            'updated_at' => now()
                        ]);

                    $user = [
                        'email' => $request->email,
                        'password' => $password
                    ];
                    
                    try {
                        Mail::to($request->email)->send(new AccountCredentialEmail($user));
                    } catch (\Exception $e) {
                        
                    }

                    $studentId = $student->id;
                }else{
                    $studentId = $studentChecker[0]->id;
                }

                // UPDATE PAYMENT
                $payment = Payment::find($paymentId);

                $payment->update(
                    [ 
                        'student_id' => $studentId,
                    ]
                );

                // insert data to payment_items
                $paymentItems = [];
                $not_replay = true;

                if($request->manual_payment == true){

                    if($request->courseId == 3) VideoLibrary::studentLibraryAccess($studentId);

                    $item = ['studentId' => $studentId, 'courseId' => $request->courseId, 'qty' => $request->course_qty];
                    array_push($paymentItems, $item);

                }else{
                    
                    if(str_contains($courses, "archives") || str_contains($courses, "pro account")) {

                        VideoLibrary::studentLibraryAccess($studentId);
                        $not_replay = false;

                    }else if( str_contains($courses, "course") 
                            || str_contains($courses, "marketing") 
                            || str_contains($courses, "executive")) {
                        

                        $qty = 1;
                        if(str_contains($courses, "+ 1")) $qty = 2;
                        else if(str_contains($courses, "20")) $qty = 20;
                        else if(str_contains($courses, "10")) $qty = 10;
                        else if(str_contains($courses, "6")) $qty = 6;

                        $item = ['studentId' => $studentId, 'courseId' => 3, 'qty' => $qty];
                        array_push($paymentItems, $item);

                    } else if(str_contains($courses, "executive") && str_contains($request->product, "technology")) {
                        $course1 = ['studentId' => $studentId, 'courseId' => 2, 'qty' => 1];
                        array_push($paymentItems, $course1);
                        $course2 = ['studentId' => $studentId, 'courseId' => 3, 'qty' => 1];
                        array_push($paymentItems, $course2);

                        // VideoLibrary::studentLibraryAccess($studentId);
                        // $not_replay = false;

                        // $qty = 1;

                        // $item = ['studentId' => $studentId, 'courseId' => 3, 'qty' => $qty];
                        // array_push($paymentItems, $item);

                    } else if(str_contains($courses, "marketing")) {
                        $qty = 1;
                        if(str_contains($request->amount, "499")) $qty = 2;
                        else if(str_contains($request->amount, "299")) $qty = 2;
                        else if(str_contains($courses, "20")) $qty = 20;
                        else if(str_contains($courses, "10")) $qty = 10;
                        else if(str_contains($courses, "6")) $qty = 6;

                        $item = ['studentId' => $studentId, 'courseId' => 1, 'qty' => $qty];
                        array_push($paymentItems, $item);

                        if($qty == 1){
                            $exeItem = ['studentId' => $studentId, 'courseId' => 2, 'qty' => $qty];
                            array_push($paymentItems, $exeItem);
                        }

                    } else if(str_contains($courses, "executive")) {
                        $qty = 1;
                        if(str_contains($request->amount, "499")) $qty = 2;
                        else if(str_contains($request->amount, "299")) $qty = 2;
                        else if(str_contains($courses, "20")) $qty = 20;
                        else if(str_contains($courses, "10")) $qty = 10;
                        else if(str_contains($courses, "6")) $qty = 6; 

                        $item = ['studentId' => $studentId, 'courseId' => 2, 'qty' => $qty];
                        array_push($paymentItems, $item);

                        if($qty == 1){
                            $marketItem = ['studentId' => $studentId, 'courseId' => 1, 'qty' => $qty];
                            array_push($paymentItems, $marketItem);
                        }

                    } else if(str_contains($courses, "technology")) {
                        $qty = 1;
                        if(str_contains($request->amount, "499")) $qty = 2;
                        else if(str_contains($request->amount, "299")) $qty = 2;
                        else if(str_contains($courses, "20")) $qty = 20;
                        else if(str_contains($courses, "10")) $qty = 10;
                        else if(str_contains($courses, "6")) $qty = 6; 
                        $item = ['studentId' => $studentId, 'courseId' => 3, 'qty' => $qty];
                        array_push($paymentItems, $item);
                    }
                }
                // dd($paymentItems);

                // UPDATE PAYMENT ITEMS
                $insertPaymentItems = Payment::insertPaymentItems($paymentId, $paymentItems);
                //end
                
                if($not_replay){

                // if(empty($studentChecker)){
                    // registrer student course
                    foreach ($paymentItems as $key => $value) {
                        Studentcourse::insertStudentCourse($value);
                    }
                    // end
                // }
                // else{
                //     // add course qty to student course
                //     foreach ($paymentItems as $key => $value) {
                        
                //         DB::table('studentcourses')
                //             ->where('studentId', $value['studentId'] )
                //             ->where('courseId', $value['courseId'] )
                //             ->where('status', 1)
                //             ->increment('quantity', $value['qty']);
                //     }
                // }
                }

                $user = [
                    'email' => $request->email,
                    'date' => now(),
                    'course' => $request->product,
                    'reference_id' => $request->reference_id,
                    'qty' => $request->quantity,
                    'amount' => $request->price,
                ];
                
                try {
                    Mail::to(env('payment_info_recipient'))->send(new PaymentConfirmationEmail($user));
                } catch (\Exception $e) {
                    
                }
            }
        
            return $payment;

        });
        
        return response(["message" => "success", "payment" => $payment], 200);
    }
}
