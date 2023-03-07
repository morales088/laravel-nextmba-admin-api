<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\MultipartUploader;
use Aws\S3\S3Client;


class Libraryfile extends Model
{
    use HasFactory;
    
    protected $guarded = ['id'];
    protected $table = 'library_files';

    public static function uploadFiles($request){

        $filePath = 'images/library/'.time().'.'.$request->library_file->extension();
        $contents = fopen($request->file('library_file'), 'rb');
        $disk = Storage::disk('s3');
        $s3 = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION')
        ]);
        $uploader = new MultipartUploader($s3, $contents, [
                'bucket' => env('AWS_BUCKET'),
            'key'    => $filePath,
        ]);

        try {
            $result = $uploader->upload();
            // dd($result['ObjectURL']);
            return $result['ObjectURL'];
        } catch (MultipartUploadException $e) {
            dd($e->getMessage());
            return $e->getMessage();
        }

        // $filePath = 'images/library/'.time().'.'.$request->file->extension();

        // $path = Storage::disk('s3')->put($filePath, fopen($request->file('file'), 'r+'));
        // $path = Storage::disk('s3')->url($filePath);
        
        // return $path;
    }
}
