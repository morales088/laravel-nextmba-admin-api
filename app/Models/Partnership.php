<?php

namespace App\Models;

use App\Models\User;
use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Partnership extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'admin_id', 'affiliate_code', 'affiliate_status', 'status', 'percentage', 'remarks'
    ];

    public function student() {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function scopeWhereStatus($query, $status) {
        return $query->where('affiliate_status', $status);
    }
}
