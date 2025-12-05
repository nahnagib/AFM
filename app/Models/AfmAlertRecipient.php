<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AfmAlertRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'alert_run_id',
        'student_id',
        'course_code',
        'status',
    ];

    public function run()
    {
        return $this->belongsTo(AfmAlertRun::class, 'alert_run_id');
    }
}
