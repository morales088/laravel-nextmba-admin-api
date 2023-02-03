<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Payment;

class WithdrawalPayment extends Model
{
    use HasFactory;

    protected $table = 'withdrawal_payments';

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
