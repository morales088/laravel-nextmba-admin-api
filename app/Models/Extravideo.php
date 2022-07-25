<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use DB;
use Image;

class Extravideo extends Model
{
    use HasFactory;
    
    protected $guarded = ['id'];

    protected $table = 'extra_videos';

    public static function extaImage($request, $extraId = null){
              
        if(!empty($request['video_image'])){
  
          // $imageName = time().'.'.$request['video_image']->extension();  
          // // dd($request->all(), $imageName);
      
          // $path = Storage::disk('s3')->put('images/other_videos_cover', $request['video_image']);
          // $path = Storage::disk('s3')->url($path);

          $filePath = 'images/other_videos_cover' . time().'.'.$request['video_image']->extension();
        
          $image = Image::make($request['video_image'])->resize(195, 275, function ($constraint) {
              $constraint->aspectRatio();
              $constraint->upsize();
          })->encode('jpg', 60);

          Storage::disk('s3')->put($filePath, $image->stream());
          $path = Storage::disk('s3')->url($filePath);

  
        }else{
          $path = $request['video_image_link'];
        }
  
        
        if($extraId){
            DB::table('extra_videos')
            ->where('id', $extraId)
            ->update(
              [
                'image_url' => $path,
                'updated_at' => now(),
              ]
            );
        }else{
            return $path;
        }
  
    }
}
