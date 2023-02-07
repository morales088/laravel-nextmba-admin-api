<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\WithdrawalPayment;
use DB;

class Payment extends Model
{
    use HasFactory;
    
    protected $guarded = ['id'];
    protected $table = 'payments';

    public static function generate_password($length = 8){
        $chars =  'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
      
        $str = '';
        $max = strlen($chars) - 1;
      
        for ($i=0; $i < $length; $i++)
          $str .= $chars[random_int(0, $max)];
      
        return $str;
    }

    public static function insertPaymentItems($paymentId = 0, $items=[]){
      // [ studentId, courseId, qty]
      
      foreach ($items as $key => $value) {
        // dd($value, $paymentId);
        
          $checker = DB::SELECT("SELECT * FROM payment_items where payment_id = $paymentId and product_id = ".$value['courseId']);
          $first_course_check = DB::SELECT("select *
                                            from payments p
                                            left join payment_items pi ON pi.payment_id = p.id
                                            where p.status = 'paid' and p.student_id = ".$value['studentId']." and pi.product_id = ".$value['courseId']);

          // dd($checker, $paymentId, $items, empty($first_course_check));
          $giftable = empty($first_course_check) ? $value['qty'] - 1 : $value['qty'];
          // dd($value, $giftable);
          if(empty($checker)){
            
            DB::table('payment_items')->insert([
              [
                  'payment_id' => $paymentId, 
                  'product_id' => $value['courseId'], 
                  'quantity' => $value['qty'],
                  'giftable' => $giftable,
                  'created_at' => now(),
                  'updated_at' => now()
              ],
            ]);
          }
      }
       
    }

    public static function getPayment($filter=[]){

      $rowPerPage = 20;
      $pagination = " LIMIT ".$rowPerPage;

      $query = [];
      $sort = " order by p.id asc";
      $queryText = "";

      $searchQuery = "";
      if(!empty($filter['search'])){
          if(is_numeric($filter["search"])){
              $searchQuery = "where P.id = ".$filter["search"];
          }else{
              $searchQuery = "where p.id like '".$filter['search']."' OR p.reference_id LIKE '%".$filter['search']."%' OR p.name like '%".$filter['search']."%' OR p.email like '%".$filter['search']."%'";
          }
      }

      if(!empty($filter['page'])){
        $pagination .= " OFFSET ".(addslashes($filter["page"]) - 1);
      }

      // check if filter column exist
      if(!empty($filter['date_created'])){
        $query += ["created_at" => "p.created_at like '%".addslashes($filter["date_created"])."%'"];
      }
      if(!empty($filter['utm_source'])){
        $query += ["utm_source" => "p.utm_source like '%".addslashes($filter["utm_source"])."%'"];
      }
      if(!empty($filter['utm_medium'])){
        $query += ["utm_medium" => "p.utm_medium like '%".addslashes($filter["utm_medium"])."%'"];
      }
      if(!empty($filter['utm_campaign'])){
        $query += ["utm_campaign" => "p.utm_campaign like '%".addslashes($filter["utm_campaign"])."%'"];
      }
      if(!empty($filter['utm_content'])){
        $query += ["utm_content" => "p.utm_content like '%".addslashes($filter["utm_content"])."%'"];
      }
      if(!empty($filter['name'])){
        $query += ["name" => "p.name like '%".addslashes($filter["name"])."%'"];
      }
      if(!empty($filter['country'])){
        $query += ["country" => "p.country like '%".addslashes($filter["country"])."%'"];
      }
      if(!empty($filter['product_name'])){
        $query += ["product_name" => "p.product like '%".addslashes($filter["product_name"])."%'"];
      }
      if(!empty($filter['status'])){
        $query += ["status" => "p.status = '%".addslashes(strtolower($filter["status"]))."%'"];
      }

      foreach ($query as $key => $value) {
          if($key === array_key_first($query) && empty($searchQuery)){ //check if this is this the first row
              $queryText .= " WHERE ".$value;
          }else{
              $queryText .= " AND ".$value;
          }
      }
      
      if(!empty($filter['sort_column']) && !empty($filter['sort_type'])){
        $sort =" order by p.".$filter["sort_column"]." ".$filter["sort_type"];
      }
      // dd("select *
      // from (select *, concat(p.first_name, ' ', p.last_name) as name
      // from payments p) as p $searchQuery$queryText $sort $pagination");

      $payments = DB::SELECT("select *
                              from (select *, concat(p.first_name, ' ', p.last_name) as full_name
                                    from payments p) as p $searchQuery$queryText $sort $pagination");

      return $payments;
    }

    public static function getAvailableCourse($student_id, $giftable_date){
      $userId = $student_id;
      $date = $giftable_date;
      
      $courses = DB::SELECT("select c.id course_id, pi.id payment_item_id, c.name course_name, pi.quantity course_qty, p.id payment_id, p.student_id, pi.giftable as unconsumed_course, IF(p.created_at > '$date', true, false) is_giftable
                          from payments p
                          left join payment_items pi ON p.id = pi.payment_id
                          left join courses c ON c.id = pi.product_id
                          where pi.status <> 0 and c.status <> 0 and c.id >= 3 and p.status = 'Paid' and p.student_id = $userId order by p.id asc");
      // dd($courses);
      $course_id = 0;
      foreach ($courses as $key => $value) {
           
        $user = [];

        if($value->course_id <> $course_id){
            $owner = collect(\DB::SELECT("SELECT email, last_login FROM students where id = $userId and status <> 0"))->first();
            array_push($user, $owner);
            $course_id = $value->course_id;
        }
          
        $gift = DB::SELECT("SELECT ci.email, ci.id gift_id, (CASE WHEN ci.status = 1 THEN 'pending' WHEN ci.status = 2 THEN 'active' END) status, last_login
                                  FROM course_invitations ci
                                  left join students s ON s.email = ci.email
                                  where ci.from_student_id = $userId and ci.from_payment_id = $value->payment_id and ci.course_id = $value->course_id and ci.status <> 0");
        
        foreach ($gift as $key2 => $value2) {
            array_push($user, $value2);
        }
        
        $value->users = $user;
          
      }

      return $courses;
  }

  public function withdrawal_payment()
  {
      return $this->hasOne(WithdrawalPayment::class, 'payment_id');
  }

}
