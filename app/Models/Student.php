<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class Student extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public static function getStudent($filter = []){

        // $students = collect(\DB::SELECT("select u.id as studentId, u.name, u.email, s.phone, s.location, s.company, s.position, s.field, IF(u.status = 1, 'active', 'deleted') as status
        //                         from users as u 
        //                         left join students as s ON s.userId = u.id
        //                         where u.role_id = 2 and u.status = 1"))->first();
        $query = [];
        $sort = " order by st.id asc";
        $queryText = "";
        
        // check if filter column exist
        if(!empty($filter['course'])){
            $query += ["course" => "st.courses like '%".addslashes($filter["course"])."%'"];
        }
        if(!empty($filter['location'])){
            $query += ["location" => "st.location like '%".addslashes($filter["location"])."%'"];
        }
        if(!empty($filter['phone'])){
            $query += ["phone" => "st.phone like '%".addslashes($filter["phone"])."%'"];
        }
        if(!empty($filter['company'])){
            $query += ["company" => "st.company like '%".addslashes($$filter["company"])."%'"];
        }
        if(!empty($filter['position'])){
            $query += ["position" => "st.position like '%".addslashes($$filter["position"])."%'"];
        }
        // if(!empty($filter['interest']!empty(){
        //     $query += ["interest" => "st.interest like '%".addslashes($$filter["interest"])."%'"];
        // }

        if(!empty($filter['sort_column']) && !empty($filter['sort_type'])){
            $sort =" order by st.".$filter["sort_column"]." ".$filter["sort_type"];
        }

        foreach ($query as $key => $value) {
            if($key === array_key_first($query)){ //check if this is this the first row
                $queryText .= " WHERE ".$value;
            }else{
                $queryText .= " AND ".$value;
            }
        }

        // dd($queryText.$sort);

        $students = DB::SELECT("select * from 
                                (select u.id as id, u.name, u.email, s.phone, s.location, s.company, s.position, s.field, IF(u.status = 1, 'active', 'deleted') as status, sc.courses
                                from users as u 
                                left join students as s ON s.userId = u.id
                                left join (select sc.userId, sc.courseId, c.name as courseName, c.description as courseDesciption, sc.status as studentCourseStatus, GROUP_CONCAT(c.name SEPARATOR ', ') courses
                                from studentcourses as sc
                                left join courses as c ON sc.courseId = c.id 
                                WHERE c.status = 1
                                GROUP BY sc.userId) as sc on u.id = sc.userId
                                where u.role_id = 2 and u.status = 1) as st".$queryText.$sort);

        return $students;
    }

    public static function getStudentCourse(){

        $studentCourse = DB::SELECT("select sc.userId, sc.courseId, c.name as courseName, c.description as courseDesciption, sc.status as studentCourseStatus
                                    from studentcourses as sc
                                    left join courses as c ON sc.courseId = c.id
                                    where sc.userId = 3");

        return $studentCourse;
    }
}
