<?php

namespace App\Models;

use DB;
use App\Models\Partnership;
use App\Models\PartnershipWithdraws;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'students';

    public function partnership() {
        return $this->hasOne(Partnership::class);
    }

    public function partnershipWithdraws() {
        return $this->hasMany(PartnershipWithdraws::class, 'student_id');
    }

    public static function getStudent($filter = []){

        $query = [];
        $sort = " order by st.id asc";
        $queryText = "";

        $rowPerPage = 20;
        $pagination = " LIMIT ".$rowPerPage;
        
        // dd($filter);

        $searchQuery = "";
        // search
        // dd(is_int($filter["search"]), is_numeric($filter["search"]));

        if(!empty($filter['search'])){
            if(is_numeric($filter["search"])){
                $searchQuery = "AND s.id = ".$filter["search"];
            }else{
                $searchQuery = "AND s.email LIKE '%".$filter['search']."%' OR s.name like '%".$filter['search']."%'";
            }
        }

        // dd($searchQuery);



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
            $query += ["company" => "st.company like '%".addslashes($filter["company"])."%'"];
        }
        if(!empty($filter['position'])){
            $query += ["position" => "st.position like '%".addslashes($filter["position"])."%'"];
        }
        // if(!empty($filter['interest']!empty(){
        //     $query += ["interest" => "st.interest like '%".addslashes($filter["interest"])."%'"];
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

        if(!empty($filter['page'])){
            $pagination .= " OFFSET ".(addslashes($filter["page"]) - 1);
        }

        $status = " WHERE s.status = 1 ";
        if(!empty($filter['status'])){

            if($filter['status'] == "all"){
                $status = " WHERE s.status in (0,1) ";
            }elseif($filter['status'] == "deactivated"){             
                $status = " WHERE s.status = 0 ";
            }else{             
                $status = " WHERE s.status = 1";
            }

        }
        // dd($status, $filter);


        // dd("select * from 
        // (select s.id, s.name, s.email, s.phone, s.location, s.company, s.position, s.field, IF(s.status = 1, 'active', 'deleted') as status, sc.courses
        // from students as s
        // left join (select sc.studentId, sc.courseId, c.name as courseName, c.description as courseDesciption, sc.status as studentCourseStatus, GROUP_CONCAT(c.name SEPARATOR ', ') courses
        // from studentcourses as sc
        // left join courses as c ON sc.courseId = c.id 
        // WHERE c.status = 1 
        // GROUP BY sc.studentId) as sc on s.id = sc.studentId WHERE s.status <> 0 $searchQuery) as st".$queryText.$sort.$pagination);

        $students = DB::SELECT("select * from 
                                (select s.id, s.name, s.email, s.phone, s.location, s.company, s.position, s.field, IF(s.status = 1, 'active', 'deleted') as status, sc.courses
                                from students as s
                                left join (select sc.studentId, sc.courseId, c.name as courseName, c.description as courseDesciption, sc.status as studentCourseStatus, GROUP_CONCAT(c.name SEPARATOR ', ') courses
                                from studentcourses as sc
                                left join courses as c ON sc.courseId = c.id 
                                WHERE c.status = 1 and sc.status <> 0
                                GROUP BY sc.studentId) as sc on s.id = sc.studentId $status $searchQuery) as st".$queryText.$sort.$pagination);

        return $students;
    }

    public static function getStudentLinks($stuendtId){

        $studentLinks = DB::SELECT("select name, link, icon, IF(status = 1, 'active', 'deleted') as status from links where studentId = $stuendtId");

        return $studentLinks;
    }

    public static function createLinks($stuendtId, $links = []){
        
        if(!empty($links['LI'])){
            Links::create([
                            'studentId' => $stuendtId,
                            'name' => 'li',
                            'link' => $links['LI'],
                        ]);
        }

        if(!empty($links['IG'])){
            Links::create([
                            'studentId' => $stuendtId,
                            'name' => 'ig',
                            'link' => $links['IG'],
                        ]);
        }

        if(!empty($links['FB'])){
            Links::create([
                            'studentId' => $stuendtId,
                            'name' => 'fb',
                            'link' => $links['FB'],
                        ]);
        }

        if(!empty($links['LI'])){
            Links::create([
                            'studentId' => $stuendtId,
                            'name' => 'li',
                            'link' => $links['LI'],
                        ]);
        }

        if(!empty($links['TG'])){
            Links::create([
                            'studentId' => $stuendtId,
                            'name' => 'tg',
                            'link' => $links['TG'],
                        ]);
        }

        if(!empty($links['WS'])){
            Links::create([
                            'studentId' => $stuendtId,
                            'name' => 'ws',
                            'link' => $links['WS'],
                        ]);
        }

        return true;

    }
    
    public static function generate_password($length = 8){
        $chars =  'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
      
        $str = '';
        $max = strlen($chars) - 1;
      
        for ($i=0; $i < $length; $i++)
          $str .= $chars[random_int(0, $max)];
      
        return $str;
      }

    public static function studentBasicAccount($student_id)
    {

        $student = Student::find($student_id);

        $student->update([
            'account_type' => 2,
            'module_count' => 24,
            'updated_at' => now(),
        ]);

        return $student;
    }
}
