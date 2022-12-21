<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class VideoLibrary extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'video_libraries';

    public static function videoLibraryLogo($request){
        $imageName = time().'.'.$request['logo_image']->extension();  
        // dd($request, $imageName);
      
        $path = Storage::disk('s3')->put('images/video_library_logo', $request['logo_image']);
        $path = Storage::disk('s3')->url($path);
          
        return $path;
    }
}
