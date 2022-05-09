<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Links;
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

    public function updateStudent(Request $request, $id){

        $students = Student::find($id);
        
        $students->update($request->only('name', 'email', 'phone', 'location', 'company', 'position', 'field') +
                        [ 'updated_at' => now()]
                        );

        $links = [];

        !empty($request->LI)? $links += ['li' => $request->LI] : '';
        !empty($request->IG)? $links += ['ig' => $request->IG] : '';
        !empty($request->FB)? $links += ['fb' => $request->FB] : '';
        !empty($request->TG)? $links += ['tg' => $request->TG] : '';
        !empty($request->WS)? $links += ['ws' => $request->WS] : '';

        foreach ($links as $key => $value) {
            // $link = collect(\DB::SELECT("SELECT * FROM links where studentId = $id and name = '$key'"))->first();
            $link = Links::find($id)->where('studentId', $id)->where('name', $key)->first();
            
            if($link){
                $link->update(
                [ 
                    'link' => $value,
                    'updated_at' => now()
                ]
                );
            }else{
                Links::create($request->only('icon') + 
                [
                    'studentId' => $id,
                    'name' => $key,
                    'link' => $value
                ]);
            }

        }
        
        $newStudentInfos =  Student::find($id);
        $newStudentLinks =  Links::where('studentId', $id)->get();

        // dd($newStudentInfos, $newStudentLinks);

        return response(["students" => $newStudentInfos, "links" => $newStudentLinks], 200);
    }
}
