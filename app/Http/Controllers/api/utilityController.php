<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Studentmodule;
use DB;

class utilityController extends Controller
{
    public function studentModules(Request $request){

        // dd($request->all());
        DB::transaction(function() use ($request) {

            $modules = DB::SELECT("select m.*
                                    from courses c
                                    left join modules m ON c.id = m.courseId
                                    where c.status <> 0 order by c.id");

            $student_courses = DB::SELECT("select sc.*
                                            from students s
                                            right join studentcourses sc ON s.id = sc.studentId
                                            where s.status <> 0");
                                            
            foreach ($modules as $key => $value) {
                
                foreach ($student_courses as $key2 => $value2) {
                    
                    // $check_student_module = Studentmodule::where('studentId', '=', $value2->studentId)->where('moduleId', '=', $value->id)->get();
                    $check_student_module = DB::SELECT("SELECT * FROM student_modules where studentId = $value2->studentId and moduleId = $value->id");

                    if(empty($check_student_module)){

                        $array = [
                            'studentId' => $value2->studentId,
                            'moduleId' => $value->id,
                        ];
        
                        if($value->start_date < now()){
                            $array['status'] = 3;
                        }
                        
                        Studentmodule::create($array);
                    }

                }

            }
            
        },5);  // try 5 times
        
    }

    public function uploadImage(Request $request){

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        
        $imageName = time().'.'.$request->image->extension();  
        // dd($request->all(), $imageName);
     
        $path = Storage::disk('s3')->put('images', $request->image);
        $path = Storage::disk('s3')->url($path);
        
        dd($path);

        // /* Store $imageName name in DATABASE from HERE */
    
        // return back()
        //     ->with('success','You have successfully upload image.')
        //     ->with('image', $path);
    }

    public function test(Request $request){
        $exe = str_contains($request->product, "executive");
        $tech = str_contains($request->product, "technology");
        dd($exe, $tech, $exe && $tech);
    }
}
