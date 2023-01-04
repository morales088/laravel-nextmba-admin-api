<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use DB;
use Image;

class Course extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'courses';

    public static function createModule($request){ // object param
            
        $module = DB::transaction(function() use ($request) {
            
            $module = Module::create($request->only('topic', 'chat_url', 'live_url', 'calendar_link', 'cover_photo') +
                [
                    'courseId' => $request->courseId,
                    'name' => $request->name,
                    'description' => $request->description,
                    'category' => $request->category,
                    'category_color' => $request->category_color,
                    // 'date' => $request->date,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                ]);

            $module["description"] = urldecode($module["description"]);

            $enrolled_student = DB::SELECT("select s.id student_id
                                            from students s
                                            left join studentcourses sc ON s.id = sc.studentId
                                            where s.status <> 0 and sc.courseId = $request->courseId");

            // get all students enrolled on specific course

            foreach ($enrolled_student as $key => $value) {
                    Studentmodule::create(
                    [
                        'studentId' => $value->student_id,
                        'moduleId' => $module->id,
                    ]);
            }
            return $module;
        });

        return $module;
    }

    public static function courseImage($request, $courseId = null){
              
        if(!empty($request['course_image'])){
  
          $imageName = time().'.'.$request['course_image']->extension();  
          // dd($request->all(), $imageName);
      
          $path = Storage::disk('s3')->put('images/courses_cover', $request['course_image']);
          $path = Storage::disk('s3')->url($path);


        // $filePath = 'images/courses_cover/' . time().'.'.$request['course_image']->extension();
        
        // $image = Image::make($request['course_image'])->resize(560, 400, function ($constraint) {
        //     $constraint->aspectRatio();
        //     $constraint->upsize();
        // })->encode('jpg', 60);

        // Storage::disk('s3')->put($filePath, $image->stream());
        // $path = Storage::disk('s3')->url($filePath);
  
        }else{
          $path = $request['course_image_link'];
        }
  
        
        if($courseId){
            DB::table('courses')
            ->where('id', $courseId)
            ->update(
              [
                'image_link' => $path,
                'updated_at' => now(),
              ]
            );
        }else{
            return $path;
        }
  
    }
}
