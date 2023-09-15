<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class Product extends Model
{
    use HasFactory;
    
    protected $guarded = ['id'];
    protected $table = 'products';

    public function product_items() {
        $items = $this->hasMany(ProductItem::class);
        $items->getQuery()->where('status','=', 1);

        return $items;
    }

    public static function courseAccessByCode($code, $student_id, $paymentId){
        $product = Product::where(DB::raw('BINARY `code`'), $code)
                            ->where('status', 1)
                            ->with('product_items')
                            ->first();

        if($product['library_access']) VideoLibrary::studentLibraryAccess($student_id);
                            
        if($product['pro_access']){ 
            VideoLibrary::studentProAccess($student_id);
            Studentcourse::addAllCourse($student_id);
            return true;
        }
        
        $starting_date = now();
        $expiration_date = now()->addMonths(12);

        $paymentItems = [];
        foreach ($product['product_items'] as $key => $value) {
            $checkCourse =  Studentcourse::where('studentId', $student_id)
                                        ->where('courseId', $value['course_id'])
                                        ->where('status', true)
                                        ->first();
                                        
            $item = ['studentId' => $student_id, 'courseId' => $value['course_id'], 'qty' => $value['quantity']];
            
            if($checkCourse){
                if($checkCourse->expirationDate < $expiration_date){
                    $checkCourse->expirationDate = $expiration_date;
                    $checkCourse->save();

                    $item = ['studentId' => $student_id, 'courseId' => $value['course_id'], 'qty' => ($value['quantity'] - 1)];
                }
            }else{
                
                $Studentcourse = Studentcourse::create(
                    [
                        'studentId' => $student_id,
                        'courseId' => $value['course_id'],
                        'starting' => $starting_date,
                        'expirationDate' => $expiration_date,
                        'quantity' => $value['quantity'],
                    ]);

            }

            array_push($paymentItems, $item);

        }

        // dd($paymentItems);
        
        // UPDATE PAYMENT ITEMS
        $insertPaymentItems = Payment::insertPaymentItems($paymentId, $paymentItems);
        //end

        return true;
    }
}
