<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class paymentController extends Controller
{
    public function index(Request $request){
        $payments = DB::SELECT("SELECT * FROM payments");


        return response()->json(["payments" => $payments], 200);
        
    }
}
