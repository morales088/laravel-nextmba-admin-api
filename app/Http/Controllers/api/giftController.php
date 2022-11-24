<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
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
}
