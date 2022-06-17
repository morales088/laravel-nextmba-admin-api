<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class utilityController extends Controller
{
    public function index(Request $request){
        dd($request->all());
    }
}
