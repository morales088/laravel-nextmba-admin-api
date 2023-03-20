<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
// use Illuminate\Support\Facades\Log;
use DB;

class VideoLibrary extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'video_libraries';

    public static function studentLibraryAccess ($student_id){
        $student = Student::find($student_id);

        $student->update(
            [ 
                'library_access' => 1,
                'pro_access' => 1,
                'updated_at' => now(),
            ]
        );

        return $student;
    }

    public static function videoLibraryLogo($request){
        $imageName = time().'.'.$request['logo_image']->extension();  
        // dd($request, $imageName);
      
        $path = Storage::disk('s3')->put('images/video_library_logo', $request['logo_image']);
        $path = Storage::disk('s3')->url($path);

        // Log::info('logo '.$path);
          
        return $path;
    }

    public static function videoLibraryCoverImage($request){
        $imageName = time().'.'.$request['cover_image']->extension();  

        // Log::info($request['cover_image']);
      
        $path = Storage::disk('s3')->put('images/video_library_speaker_cover', $request['cover_image']);
        $path = Storage::disk('s3')->url($path);
        // dd($path);

        // dd($request, $imageName);

        // Log::info($path);
          
        return $path;
    }

    // public static function videoLibraryCoverImage($request){
    //     $imageName = time().'.'.$request['cover_image']->extension();  
    //     // dd($request, $imageName);
      
    //     $path = Storage::disk('s3')->put('images/video_library_speaker_cover', $request['cover_image']);
    //     $path = Storage::disk('s3')->url($path);
          
    //     return $path;
    // }
}
