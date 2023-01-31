<?php

namespace App\Models;

use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PartnershipWithdraws extends Model
{
    use HasFactory;

    protected $table = 'partnership_withdraws';
    protected $fillable = ['commission_status', 'remarks', 'withdraw_method'];

    public function student() {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
