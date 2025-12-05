<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AfmAlertRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'window_id',
        'triggered_by',
        'run_type',
    ];

    public function recipients()
    {
        return $this->hasMany(AfmAlertRecipient::class, 'alert_run_id');
    }
}
