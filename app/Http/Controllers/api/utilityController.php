<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Studentmodule;
use DB;
use Image;

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
        
        // $imageName = time().'.'.$request->image->extension(); 
     
        // $path = Storage::disk('s3')->put('images', $request->image);
        // $path = Storage::disk('s3')->url($path);

        // dd($path);
        
        // $avatar = $request->file('image');
        // $extension = $request->file('image')->getClientOriginalExtension();

        // $filename = md5(time()).'_'.$avatar->getClientOriginalName();

        // $image = Image::make($avatar)->resize(500, 500)->encode($extension);
        

        // $imageName = time().'.'.$request->image->extension(); 
        // // $imageName = time().'.'.$image->getClientOriginalExtension();
        // $path = Storage::disk('s3')->put('test/'.$imageName, $image);
        // $path = Storage::disk('s3')->url($path);

        $filePath = '/images/' . $request->file('image')->hashName();

        $image = Image::make($request->file('image'))->resize(500, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->encode('jpg', 60);

        Storage::disk('s3')->put($filePath, $image->stream());
        $path = Storage::disk('s3')->url($filePath);
        
        dd($path);

        // /* Store $imageName name in DATABASE from HERE */
    
        // return back()
        //     ->with('success','You have successfully upload image.')
        //     ->with('image', $path);
    }
}
