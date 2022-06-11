<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Mail;
use App\Mail\AccountCredentialEmail;
use App\Models\User;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Studentcourse;
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
            'hitpay_id' => 'required|string',
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
        $payment = Payment::create($request->only('reference_id', 'hitpay_id') +
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
            if(str_contains($paymentInfo->product, "Bundle")) {
                $course1 = ['studentId' => $student->id, 'courseId' => 1, 'qty' => 1];
                array_push($paymentItems, $course1);
                $course2 = ['studentId' => $student->id, 'courseId' => 2, 'qty' => 1];
                array_push($paymentItems, $course2);
            } else if(str_contains($paymentInfo->product, "Marketing")) {
                $qty = 1;
                if(str_contains($paymentInfo->product, "10")) $qty = 20;
                else if(str_contains($paymentInfo->product, "5")) $qty = 10;
                else if(str_contains($paymentInfo->product, "3")) $qty = 6;
                $item = ['studentId' => $student->id, 'courseId' => 1, 'qty' => $qty];
                array_push($paymentItems, $item);
            } else if(str_contains($paymentInfo->product, "Executive")) {
                $qty = 1;
                if(str_contains($paymentInfo->product, "10")) $qty = 20;
                else if(str_contains($paymentInfo->product, "5")) $qty = 10;
                else if(str_contains($paymentInfo->product, "3")) $qty = 6; 
                $item = ['studentId' => $student->id, 'courseId' => 2, 'qty' => $qty];
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

        $payment = COLLECT(\DB::SELECT("SELECT *, concat(first_name, ' ', last_name) name FROM payments where id = $id"))->first();

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
}
