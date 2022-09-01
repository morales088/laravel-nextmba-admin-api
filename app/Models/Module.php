<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use DB;
use Image;

class Module extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public static function moduleImage($request, $modulesId = null){
              
        if(!empty($request['module_cover_image'])){
  
          // $imageName = time().'.'.$request['module_cover_image']->extension();  
          // // dd($request->all(), $imageName);
      
          // $path = Storage::disk('s3')->put('images/modules_cover', $request['module_cover_image']);
          // $path = Storage::disk('s3')->url($path);

          
          $filePath = 'images/modules_cover' . time().'.'.$request['module_cover_image']->extension();
        
          $image = Image::make($request['module_cover_image'])->resize(400, 200, function ($constraint) {
              $constraint->aspectRatio();
              $constraint->upsize();
          })->encode('jpg', 60);

          Storage::disk('s3')->put($filePath, $image->stream());
          $path = Storage::disk('s3')->url($filePath);
  
        }else{
          $path = $request['module_cover_link'];
        }
  
        
        if($modulesId){
            DB::table('modules')
            ->where('id', $modulesId)
            ->update(
              [
                'cover_photo' => $path,
                'updated_at' => now(),
              ]
            );
        }else{
            return $path;
        }
  
    }
}
