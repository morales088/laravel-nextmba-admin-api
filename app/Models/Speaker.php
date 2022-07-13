<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use DB;

class Speaker extends Model
{
    use HasFactory;
    
    protected $guarded = ['id'];

    public static function speakerImage($request, $speakerId = null){
              
        if(!empty($request['speaker_image'])){
  
          $imageName = time().'.'.$request['speaker_image']->extension();  
          // dd($request->all(), $imageName);
      
          $path = Storage::disk('s3')->put('images/speakers/profile', $request['speaker_image']);
          $path = Storage::disk('s3')->url($path);
  
        }else{
          $path = $request['speaker_link'];
        }
  
        
        if($speakerId){
            DB::table('speakers')
            ->where('id', $speakerId)
            ->update(
              [
                'profile_path' => $path,
                'updated_at' => now(),
              ]
            );
        }else{
            return $path;
        }
  
    }
    public static function speakerCompany($request, $speakerId = null){
              
        if(!empty($request['company_image'])){
  
          $imageName = time().'.'.$request['company_image']->extension();  
          // dd($request->all(), $imageName);
      
          $path = Storage::disk('s3')->put('images/speakers/company', $request['company_image']);
          $path = Storage::disk('s3')->url($path);
  
        }else{
          $path = $request['company_link'];
        }
  
        
        if($speakerId){
            DB::table('speakers')
            ->where('id', $speakerId)
            ->update(
              [
                'company_path' => $path,
                'updated_at' => now(),
              ]
            );
        }else{
            return $path;
        }
  
    }
}
