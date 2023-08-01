<?php

namespace App\Models;

use DB;
use App\Models\Links;
use App\Models\Affiliate;
use App\Models\Studentcourse;
use App\Models\AffiliateWithdraws;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'students';

    public function affiliate() {
        return $this->hasOne(Affiliate::class);
    }

    public function affiliateWithdraws() {
        return $this->hasMany(AffiliateWithdraws::class, 'student_id');
    }

    public function courses() {
        return $this->hasMany(Studentcourse::class, 'studentId');
    }

    public function links() {
        return $this->hasMany(Links::class, 'studentId');
    }

    public function scopeWithFilters($query, $filters) {

        // search filter
        if (isset($filters['search']) && !empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('email', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('name', 'like', '%' .$filters['search'] . '%');
            });
        }
        
        // filter by status
        if (isset($filters['status']) && $filters['status'] != 'all') {
            $query->where('status', $filters['status'] == 'active' ? 1 : 0);
        } else {
            $query->whereIn('status', [0, 1]);
        }

        // filter by course_id (students with the specified course_id)
        if (isset($filters['course_id']) && is_array($filters['course_id'])) {
            $query->whereHas('courses', function ($q) use ($filters) {
                $q->whereIn('studentcourses.courseId', $filters['course_id'])
                    ->join('courses', 'studentcourses.courseId', '=', 'courses.id')
                    ->where('courses.status', 1) 
                    ->where('studentcourses.status', '<>', 0);
            });
        }

        // filter by course_id (exclude students with the specified course_id)
        if (isset($filters['without_course_id']) && is_array($filters['without_course_id'])) {
            $query->whereDoesntHave('courses', function ($q) use ($filters) {
                $q->whereIn('studentcourses.courseId', $filters['without_course_id'])
                    ->join('courses', 'studentcourses.courseId', '=', 'courses.id')
                    ->where('courses.status', 1) 
                    ->where('studentcourses.status', '<>', 0);
            });
        }

        // filter by other attributes
        foreach (['location', 'phone', 'company', 'position'] as $attribute) {
            if (isset($filters[$attribute]) && !empty($filters[$attribute])) {
                $query->where($attribute, 'like', '%' . $filters[$attribute] . '%');
            }
        }

        return $query;
    }

    public static function getStudents($filters = [], $paginate = false) {

        $sortColumn = $filters['sort_column'] ?? 'id';
        $sortType = $filters['sort_type'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 20;

        $query = Student::query();

        $students = $query->with(['courses' => function ($q) {
            $q->select('studentId', 'courseId', 'course_type', 'name as courseName', 'description as courseDescription', 'studentcourses.status as studentCourseStatus')
                ->join('courses', 'studentcourses.courseId', '=', 'courses.id')
                ->where('courses.status', 1)
                ->where('studentcourses.status', '<>', 0);
        }])
        ->with(['links' => function ($q) {
            $q->select('studentId', 'name', 'link', 'icon')
                ->selectRaw("IF (status = 1, 'active', 'deleted') as status");
        }])
        ->select('students.id', 'name', 'email', 'phone', 'location', 'company', 'position', 'account_type', 'status')
        ->withFilters($filters)
        ->orderBy($sortColumn, $sortType);

        // check if return a paginated result     
        if ($paginate) {
            $students = $students->paginate($perPage);
        } else {
            $students = $students->get();
        }

        // map course_type and account_type
        $courseTypeMapping = [ 1 => 'Paid', 2 => 'Manual', 3 => 'Gifted' ];
        $accountTypeMapping = [ 1 => 'Trial', 2 => 'Basic', 3 => 'Pro' ];

        foreach ($students as $student) {
            $student->account_type = $accountTypeMapping[$student->account_type] ?? 'Unknown';
            
            $courseTypes = [];
            foreach ($student->courses as $course) {
                $course->course_type = $courseTypeMapping[$course->course_type] ?? 'Unknown';

                // add course type to all courses type array
                $courseType = $course->course_type;
                if (!in_array($courseType, $courseTypes)) {
                    $courseTypes[] = $courseType;
                }
            }

            $student->all_courses_types = implode(', ', $courseTypes);
        }

        return $students;
    }

    // old  get students filter
    // public static function getStudent($filter = []){

    //     $query = [];
    //     $sort = " order by st.id asc";
    //     $queryText = "";

    //     $rowPerPage = 20;
    //     $pagination = " LIMIT ".$rowPerPage;
        
    //     // dd($filter);

    //     $searchQuery = "";
    //     // search
    //     // dd(is_int($filter["search"]), is_numeric($filter["search"]));

    //     if(!empty($filter['search'])){
    //         if(is_numeric($filter["search"])){
    //             $searchQuery = "AND s.id = ".$filter["search"];
    //         }else{
    //             $searchQuery = "AND s.email LIKE '%".$filter['search']."%' OR s.name like '%".$filter['search']."%'";
    //         }
    //     }

    //     // dd($searchQuery);



    //     // check if filter column exist
    //     if(!empty($filter['course'])){
    //         $query += ["course" => "st.courses like '%".addslashes($filter["course"])."%'"];
    //     }
    //     if(!empty($filter['location'])){
    //         $query += ["location" => "st.location like '%".addslashes($filter["location"])."%'"];
    //     }
    //     if(!empty($filter['phone'])){
    //         $query += ["phone" => "st.phone like '%".addslashes($filter["phone"])."%'"];
    //     }
    //     if(!empty($filter['company'])){
    //         $query += ["company" => "st.company like '%".addslashes($filter["company"])."%'"];
    //     }
    //     if(!empty($filter['position'])){
    //         $query += ["position" => "st.position like '%".addslashes($filter["position"])."%'"];
    //     }
    //     // if(!empty($filter['interest']!empty(){
    //     //     $query += ["interest" => "st.interest like '%".addslashes($filter["interest"])."%'"];
    //     // }

    //     if(!empty($filter['sort_column']) && !empty($filter['sort_type'])){
    //         $sort =" order by st.".$filter["sort_column"]." ".$filter["sort_type"];
    //     }

    //     foreach ($query as $key => $value) {
    //         if($key === array_key_first($query)){ //check if this is this the first row
    //             $queryText .= " WHERE ".$value;
    //         }else{
    //             $queryText .= " AND ".$value;
    //         }
    //     }

    //     if(!empty($filter['page'])){
    //         $pagination .= " OFFSET ".(addslashes($filter["page"]) - 1);
    //     }

    //     $status = " WHERE s.status = 1 ";
    //     if(!empty($filter['status'])){

    //         if($filter['status'] == "all"){
    //             $status = " WHERE s.status in (0,1) ";
    //         }elseif($filter['status'] == "deactivated"){             
    //             $status = " WHERE s.status = 0 ";
    //         }else{             
    //             $status = " WHERE s.status = 1";
    //         }

    //     }
        
    //     $students = DB::SELECT("select * from 
    //                             (select s.id, s.name, s.email, s.phone, s.location, s.company, s.position, s.field, IF(s.status = 1, 'active', 'deleted') as status, sc.courses, sc.account_type
    //                             from students as s
    //                             left join (select sc.studentId, sc.courseId, c.name as courseName, c.description as courseDesciption, sc.status as studentCourseStatus, GROUP_CONCAT(c.name SEPARATOR ', ') courses,
    //                             GROUP_CONCAT(DISTINCT IF(sc.course_type = 1, 'Paid', IF(sc.course_type = 2 , 'Manual', 'Gifted') ) SEPARATOR ' + ') account_type
    //                             from studentcourses as sc
    //                             left join courses as c ON sc.courseId = c.id 
    //                             WHERE c.status = 1 and sc.status <> 0
    //                             GROUP BY sc.studentId) as sc on s.id = sc.studentId $status $searchQuery) as st".$queryText.$sort.$pagination);

    //     return $students;
    // }

    // public static function getStudentLinks($studentId) {

    //     $studentLinks = DB::table('links')
    //         ->select('name', 'link', 'icon')
    //         ->selectRaw("IF (status = 1, 'active', 'deleted') as status")
    //         ->where('studentId', $studentId)
    //         ->get();

    //     return $studentLinks;
    // }

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
        $module_count = env('MODULE_PER_COURSE');

        $student->update([
            'account_type' => 2,
            'module_count' => $module_count,
            'updated_at' => now(),
        ]);

        return $student;
    }
}
