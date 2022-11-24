<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;

class GiftQtySeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compute:gift';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'compute number of available gift';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $payments = DB::SELECT("select p.id payment_id, p.student_id, p.price, pi.*
                                from payments p
                                left join payment_items pi ON p.id = pi.payment_id
                                where p.status = 'paid' and pi.status = 1 order by p.student_id ASC, p.id ASC");
                    //  dd($payments);           
        $last_student_id = 0;

        foreach ($payments as $key => $value) {
            // $gifts = DB::SELECT("SELECT * FROM course_invitations 
            //                     WHERE from_student_id = $value->student_id 
            //                     and from_payment_id = $value->payment_id
            //                     and course_id = $value->product_id
            //                     and status = 2");
                            
            $gifts = DB::TABLE("course_invitations")
                        ->WHERE("from_student_id", $value->student_id)
                        ->WHERE("from_payment_id", $value->payment_id)
                        ->WHERE("course_id", $value->product_id)
                        ->WHERE("status", 2)
                        ->get();

            // dd($value, $gifts, $gifts->count() - 1);

            // update giftable
            // $giftable = ($value->quantity - 1 ) - $gifts->count();
            $giftable = ($last_student_id == $value->student_id) ? $value->quantity : (($value->quantity - 1 ) - $gifts->count()) ;

            // if($value->payment_id == 197){
            //     dd($value, $last_student_id, $giftable); 
            // }

            DB::table('payment_items')
                        ->where('id', $value->id)
                        ->update([
                            'giftable' => $giftable, 
                            'updated_at' => now()
                        ]);



            $this->line("Payment id - ".$value->payment_id);
                        
            $last_student_id = $value->student_id;
        }

        // dd($payments);
        $this->line("DONE.");
    }
}
