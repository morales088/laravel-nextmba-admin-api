<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Mail\GiftAccountCredential;
use App\Mail\GiftEmail;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Course;
use App\Models\Studentcourse;
use App\Models\Courseinvitation;
use App\Models\PaymentItem;
use Mail;
use DB;

class giftController extends Controller
{
    public function paymentCourses(Request $request, $student_id){

        $request->query->add(['student_id' => $student_id]);

        $request->validate([
            'student_id' => 'required|numeric|exists:students,id',
        ]);

        $student_id = $request->student_id;
        $giftable_date = env('GIFTABLE_DATE');
        
        $payment_courses = Payment::getAvailableCourse($student_id, $giftable_date);
        
        return response(["payment_courses" => $payment_courses], 200);
    }

    public function sendGift(Request $request){
                
        $request->validate([
            'course_id' => 'required|numeric|min:1|exists:courses,id',
            'payment_id' => 'required|numeric|min:1|exists:payments,id',
            'student_id' => 'required|numeric|min:1|exists:students,id',
            'email' => 'required|email',
        ]);

        $userId = $request->student_id;
        $fe_link = env('FRONTEND_LINK');
        $giftable_gift = env('GIFTABLE_DATE');

        $check_course_id = COLLECT(\DB::SELECT("select pi.* 
                                                from payments p
                                                left join payment_items pi ON pi.payment_id = p.id
                                                where p.id = $request->payment_id 
                                                and pi.product_id = $request->course_id 
                                                and p.status = 'paid'
                                                and pi.product_id = 3"))->first();

        $available_course_per_payment = COLLECT(\DB::SELECT("select pi.* 
                                                from payments p
                                                left join payment_items pi ON pi.payment_id = p.id
                                                where p.id = $request->payment_id and pi.product_id = $request->course_id and p.status = 'paid'"))->first();

        $check_recipient_course = DB::SELECT("select *
                    from students s
                    left join studentcourses sc ON s.id = sc.studentId
                    where s.status <> 0 and sc.status <> 0 and s.email = '$request->email' and sc.courseId = $request->course_id");
        
        $is_giftable = COLLECT(\DB::SELECT("SELECT * from payments where id = $request->payment_id and created_at > '$giftable_gift'"))->first();

        if($available_course_per_payment->giftable <= 0 || !empty($check_recipient_course) || empty($is_giftable) || !empty($check_course_id)){
            return response()->json(["message" => "zero courses available / recipient already has this course / course expired"], 422);
        }

        // dd($available_course_per_payment, $check_recipient_course, $is_giftable);
        
        $DBtransaction = DB::transaction(function() use ($request, $userId, $fe_link, $giftable_gift, $check_recipient_course, $available_course_per_payment) {
            
            $sender = Student::find($userId);
            $course = Course::find($request->course_id);
            
            $email_check = Student::WHERE('email', '=', $request->email)->first();
            
            if($email_check){
                // check if student exist
                //     return student id

                // dd($email_check->id);
                $student_id = $email_check->id;

                // notify user thru email
                $user = [
                    'email_sender' => $sender->email,
                    'course' => $course->name
                ];
                // dd($user);
                Mail::to($request->email)->send(new GiftEmail($user));

            }else{
                // else create student
                //     create student acc
                //     return student id
                $name = strtok($request->email, '@');
                
                $password = Student::generate_password();
                $student = Student::create($request->only('phone', 'location', 'company', 'position', 'field') + 
                            [
                                'name' => $name,
                                'email' => $request->email,
                                'password' => Hash::make($password),
                                'updated_at' => now()
                            ]);

                $student_id = $student->id;

                // send account info thru email
                $user = [
                    'email_sender' => $sender->email,
                    'course' => $course->name,
                    'email' => $request->email,
                    'password' => $password
                ];
                // dd($user);
                Mail::to($request->email)->send(new GiftAccountCredential($user));

            }
            
            // dd($student_id, $check_available_qty, --$check_available_qty->quantity);

            // add course to student
            $data = ['studentId' => $student_id, 'courseId' => $request->course_id];
            Studentcourse::insertStudentCourse($data);

            // deduct course to student_course table

            DB::table('payment_items')
            ->where('id', $available_course_per_payment->id)
            ->update(['giftable' => --$available_course_per_payment->giftable, 'updated_at' => now()]);

            
            // insert data to course_invitations
            return Courseinvitation::create($request->only('icon') + 
            [
                'from_student_id' => $userId,
                'from_payment_id' => $request->payment_id,
                'course_id' => $request->course_id,
                'email' => $request->email,
                'status' => 2,
                // 'code' => $code              change code to nullable
            ]);
            
        });
        
        return response(["message" => "Gift successfully sent."], 200);

    }

    public function updatePaymentItem(Request $request, $item_id){
                
        $request->query->add(['item_id' => $item_id]);
        $request->validate([
            'item_id' => 'required|numeric|min:1|exists:payment_items,id',
            'course_qty' => 'required|numeric|min:1',
        ]);

        $payment_item = PaymentItem::find($item_id);

        $min_qty = $payment_item->quantity - $payment_item->giftable;
        
        if($min_qty > $request->course_qty){
            return response()->json(["message" => "course_qty too low."], 422);
        }

        $giftable = $request->course_qty - $min_qty;
        // dd($payment_item, $giftable);

        $payment_item->update(
                            [ 
                                'quantity' => $request->course_qty,
                                'giftable' => $giftable,
                                'updated_at' => now()
                            ]
                            );

        // DB::table('payment_items')
        // ->where('id', $item_id)
        // ->update(['quantity' => $request->course_qty, 'updated_at' => now()]);
        

        return response(["message" => "Successfully updated product quantity."], 200);

    }
}
