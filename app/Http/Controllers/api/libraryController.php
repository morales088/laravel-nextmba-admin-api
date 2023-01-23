<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\VideoLibrary;
use App\Models\Libraryfile;

use Illuminate\Support\Facades\Log;

use DB;

class libraryController extends Controller
{
    public function index(Request $request){
                
        $library = $request->validate([
            'broadcast_status' => [
                        'numeric',
                        Rule::in(['0', '1']),
                    ],
            'status' => [
                        'numeric',
                        Rule::in(['0', '1']),
                    ],
        ]);

        $broadcast_status = $request->query('broadcast_status');
        $status = $request->query('status', 1);

        $currentPage = $request->query('page', 1);
        $perPage = $request->query('per_page', 10);

        if (empty($request->query('offset'))) {
            $offset = ($currentPage - 1) * $perPage;
        } else {
            $offset = $request->query('offset');
        }
        
        $video_libraries = VideoLibrary::query();

        $video_libraries = $video_libraries->where('status', $status);

        if(!empty($broadcast_status)) $video_libraries = $video_libraries->where('broadcast_status', $broadcast_status); ;

        $video_libraries = $video_libraries->offset($offset)
                                ->limit($perPage)
                                ->orderBy('date', 'DESC')
                                ->orderBy('name', 'ASC')
                                ->get();

        $totalOrder = VideoLibrary::where('status', $status)->count();
        
        $videos = new LengthAwarePaginator($video_libraries, $totalOrder, $perPage, $currentPage, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return response(["video_libraries" => $videos], 200);

    }

    public function perlLibrary(Request $request, $id){
                
        $request->query->add(['id' => $id]);
        $speaker = $request->validate([
            'id' => 'required|string|exists:video_libraries,id',
        ]);
        
        $video_library = VideoLibrary::where('id', $id)->first();

        return response(["video_library" => $video_library], 200);

    }

    public function library(Request $request, $id=null){
        // dd($request->all(), $id);

        // Log::info($id);
        
        $speaker = $request->validate([
            'name' => 'required|string',
            // 'name' => 'required|string|unique:video_libraries,name',
            // 'uid' => 'required|string',
            // 'date' => 'required|string',
            'cover_image' => 'image|mimes:jpeg,png,jpg|max:3048',
            'cover_delete' => [
                        'string',
                        Rule::in(['true', 'false']),
                    ],
            'logo_image' => 'image|mimes:jpeg,png,jpg|max:3048',
            'logo_delete' => [
                        'string',
                        Rule::in(['true', 'false']),
                    ],
            'category' => 'string|nullable|sometimes',
            'broadcast_status' => [
                        'numeric',
                        Rule::in(['0', '1']),
                    ],
            'status' => [
                        'numeric',
                        Rule::in(['0', '1']),
                    ],
        ]);

        if($id){
            // dd(1);
            $request->query->add(['id' => $id]);
            $request->validate([
                'id' => 'required|string|exists:video_libraries,id',
                'name' => [
                    Rule::unique('video_libraries')->ignore($id),
                ],
            ]);

            if($request->logo_delete == 'true'){
                $request->query->add(['logo' => null]);
            }elseif(!empty($request->logo_image)){
                $logo_path = VideoLibrary::videoLibraryLogo($request->all());
                $request->query->add(['logo' => $logo_path]);
            }

            $cover_path = '';

            if($request->cover_delete == 'true'){
                $request->query->add(['cover_image' => null]);
            }elseif(!empty($request->cover_image)){
                $cover_path = VideoLibrary::videoLibraryCoverImage($request->all());
                $request->query->add(['cover_image' => $cover_path]);
            }

            
            $video_library = VideoLibrary::find($id);
            $video_library->update($request->only('name', 'description', 'uid', 'video_length', 'speaker', 'cover_image', 'logo', 'category', 'date', 'broadcast_status', 'status') +
                        [ 'updated_at' => now()]
                        );

            if (!empty($request->cover_image)) {
                $video_library->cover_image = $cover_path;
                $video_library->save();
            }
            
        }else{
            // dd(2);
            $request->validate([
                'name' => 'required|string|unique:video_libraries,name',
            ]);

            if(!empty($request->logo_image)){
                $logo_path = VideoLibrary::videoLibraryLogo($request->all());
                $request->query->add(['logo' => $logo_path]);
            }

            $cover_path = '';

            if(!empty($request->cover_image)){
                $cover_path = VideoLibrary::videoLibraryCoverImage($request->all());
                $request->query->add(['cover_image' => $cover_path]);
            }

            $video_library = VideoLibrary::create($request->only('description', 'uid', 'video_length', 'speaker', 'cover_image', 'logo', 'category', 'date', 'broadcast_status', 'status') +
                        [
                            'name' => $request->name,
                            'updated_at' => now()
                        ]);

            if (!empty($request->cover_image)) {
                $video_library->cover_image = $cover_path;
                $video_library->save();
            }
        }

        return response(["video_library" => $video_library], 200);
    }

    public function addFiles(Request $request){

        $request->validate([
            'library_id' => 'required|exists:video_libraries,id',
            // 'file' => 'required',
            'name' => 'string',
        ]);

        if(!empty($request->file)){
            $uploadFile = Libraryfile::uploadFiles($request);
            $request->query->add(['link' => $uploadFile]);
        }

        // dd($uploadFile);

        $files = Libraryfile::create($request->only('link') + 
                [
                    'libraryId' => $request->library_id,
                    'name' => $request->name,
                ]);

        return response(["files" => $files], 200);
        
    }

    public function updateFiles(Request $request, $id){

        $request->query->add(['id' => $id]);
        
        $request->validate([
            'id' => 'required|exists:video_libraries,id',
            'status' => [
                        'string',
                        Rule::in(['0', '1']),
                    ],
            'file_delete' => [
                        'string',
                        Rule::in(['true', 'false']),
                    ],
        ]);

        if(!empty($request->file)){
            $uploadFile = Libraryfile::uploadFiles($request);
            $request->query->add(['link' => $uploadFile]);
        }
        
        if($request->file_delete == true){
            $request->query->add(['link' => null]);
        }

        $file = Libraryfile::find($request->id);
    
        $file->update($request->only('name', 'link', 'status') +
                        [ 'updated_at' => now()]
                        );

        return response(["file" => $file], 200);
        
    }

    public function getFiles(Request $request, $library_id = 0){
                
        $request->query->add(['library_id' => $library_id]);
        
        $request->validate([
            'library_id' => 'required|exists:video_libraries,id',
        ]);

        
        if($library_id > 0){
            
            $files = DB::SELECT("select * from library_files where libraryId = $library_id and status <> 0");
            
        }else{

            $files = DB::SELECT("select * from library_files where status <> 0");

        }
        return response(["files" => $files], 200);
    }

    
}
