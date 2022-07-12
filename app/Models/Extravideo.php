<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use DB;

class Extravideo extends Model
{
    use HasFactory;
    
    protected $guarded = ['id'];

    protected $table = 'extra_videos';

    public static function extaImage($request, $extraId = null){
              
        if(!empty($request['video_image'])){
  
          $imageName = time().'.'.$request['video_image']->extension();  
          // dd($request->all(), $imageName);
      
          $path = Storage::disk('s3')->put('images/other_videos_cover', $request['video_image']);
          $path = Storage::disk('s3')->url($path);
  
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
