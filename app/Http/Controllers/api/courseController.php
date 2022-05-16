<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Course;
use DB;

class courseController extends Controller
{
    public function index(Request $request){
        $courses = DB::SELECT("select c.id course_id, c.name, c.description, count(s.id) total_students, s.status, (CASE WHEN s.status = 0 THEN 'deleted' WHEN s.status = 1 THEN 'active' END) as status_code, c.created_at, c.updated_at
                                from students s
                                left join studentcourses sc ON sc.studentId = s.id
                                left join courses c ON c.id = sc.courseId
                                where s.status <> 0 and sc.status <> 0 and c.status <> 0
                                group by c.id");

        return response()->json(["courses" => $courses], 200);
        
    }
}
