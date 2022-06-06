<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class Payment extends Model
{
    use HasFactory;
    
    protected $guarded = ['id'];
    protected $table = 'payments';

    public static function generate_password($length = 20){
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
          if(empty($checker)){
            
            DB::table('payment_items')->insert([
              [
                  'payment_id' => $paymentId, 
                  'product_id' => $value['courseId'], 
                  'quantity' => $value['qty'],
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
              $searchQuery = "where p.reference_id LIKE '%".$filter['search']."%' OR p.name like '%".$filter['search']."%' OR p.email like '%".$filter['search']."%'";
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
      if(!empty($filter['product_name'])){
        $query += ["product_name" => "p.product like '%".addslashes($filter["product_name"])."%'"];
      }
      if(!empty($filter['status'])){
        $query += ["status" => "p.status like '%".addslashes($filter["status"])."%'"];
      }

      foreach ($query as $key => $value) {
          if($key === array_key_first($query)){ //check if this is this the first row
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
      //       from payments p) as p $searchQuery$queryText$sort$pagination");

      $payments = DB::SELECT("select *
                              from (select *, concat(p.first_name, ' ', p.last_name) as name
                                    from payments p) as p $searchQuery$queryText $sort $pagination");

      return $payments;
    }

}
