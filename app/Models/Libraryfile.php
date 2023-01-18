<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Libraryfile extends Model
{
    use HasFactory;
    
    protected $guarded = ['id'];
    protected $table = 'library_files';

    public static function uploadFiles($request){

        $filePath = 'images/library/'.time().'.'.$request->file->extension();

        $path = Storage::disk('s3')->put($filePath, fopen($request->file('file'), 'r+'));
        $path = Storage::disk('s3')->url($filePath);
        
        return $path;
    }
}
