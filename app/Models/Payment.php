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

}
