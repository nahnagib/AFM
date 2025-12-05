<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AfmFormAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_template_id',
        'scope_type',
        'scope_key',
        'term_code',
    ];

    public function template()
    {
        return $this->belongsTo(AfmFormTemplate::class, 'form_template_id');
    }
}
