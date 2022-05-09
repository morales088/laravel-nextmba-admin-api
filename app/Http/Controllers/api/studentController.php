<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use DB;

class studentController extends Controller
{
    public function index(Request $request){

        $login = $request->validate([
            'page' => 'required|numeric|min:1',
            'sort_column' => 'required|string',
            'sort_type' => 'required|string',
            'course' => 'string',
            'location' => 'string',
            'phone' => 'string',
            'company' => 'string',
            'position' => 'string',
            'interest' => 'string',
            
        ]);

        $query_filter = [];

        !empty($request->course)? $query_filter += ['course' => $request->course] : '';
        !empty($request->location)? $query_filter += ['location' => $request->location] : '';
        !empty($request->phone)? $query_filter += ['phone' => $request->phone] : '';
        !empty($request->company)? $query_filter += ['company' => $request->company] : '';
        !empty($request->position)? $query_filter += ['position' => $request->position] : '';
        !empty($request->interest)? $query_filter += ['interest' => $request->interest] : '';

        !empty($request->page)? $query_filter += ['page' => $request->page] : '';
        (!empty($request->sort_column) && !empty($request->sort_column) )? $query_filter += ['sort_column' => $request->sort_column, 'sort_type' => $request->sort_type] : '';

        // dd('asc' === 'ASC');
    
        $students = Student::getStudent($query_filter);

        // dd($students);
        
        foreach ($students as $key => $value) {
            
            $studentLinks = Student::getStudentLinks(1);
            $value->links = $studentLinks;
        }

        return response(["students" => $students], 200);
    }
}
