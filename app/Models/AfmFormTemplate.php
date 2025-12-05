<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AfmFormTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'code',
        'form_type',
        'schema_json',
        'status',
    ];

    protected $casts = [
        'schema_json' => 'array',
    ];

    public function assignments()
    {
        return $this->hasMany(AfmFormAssignment::class, 'form_template_id');
    }
}
