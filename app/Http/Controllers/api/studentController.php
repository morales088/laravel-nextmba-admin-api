<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
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
        !empty($request->search)? $query_filter += ['search' => $request->search] : '';

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

    public function activateDeactivate(Request $request, $id){

        $request->query->add(['id' => $id]);

        $studentId = $request->validate([
            'id' => 'numeric|min:1|exists:Students,id',
            'status' => 'string',
        ]);
        $status = 1;

        if($request->status == 'activate'){
            $status = 1;
        }elseif($request->status == 'deactivate'){
            $status = 0;
        }

        $students = Student::find($id);
        
        $students->update(
                    [ 
                        'updated_by' => auth('api')->user()->id,
                        'status' => $status,
                        // 'updated_at' => now()
                    ]
                    );

        return response(["message" => "successfully updated this student"], 200);

    }

    public function changePassword(Request $request, $id){

        $request->query->add(['id' => $id]);

        $studentId = $request->validate([
            'id' => 'numeric|min:1|exists:Students,id',
        ]);

        $textPassword = Str::random(10);
        $hashPasword = Hash::make($textPassword);

        

        $students = Student::find($id);
        
        $students->update(
                    [ 
                        'password' => $hashPasword,
                        'updated_by' => auth('api')->user()->id,
                        // 'updated_at' => now()
                    ]
                    );

        return response(["newPassword" => $textPassword, "message" => "successfully updated this student"], 200);

        // dd($textPassword, $hashPasword);

    }
}
