<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use DB;

class Module extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public static function moduleImage($request, $modulesId = null){
              
        if(!empty($request['module_image'])){
  
          $imageName = time().'.'.$request['module_image']->extension();  
          // dd($request->all(), $imageName);
      
          $path = Storage::disk('s3')->put('images/modules_cover', $request['module_image']);
          $path = Storage::disk('s3')->url($path);
  
        }else{
          $path = $request['module_link'];
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
