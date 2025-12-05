<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormCourseScope extends Model
{
    protected $table = 'form_course_scope'; // Explicit table name
    protected $guarded = ['id'];

    protected $casts = [
        'is_required' => 'boolean',
        'applies_to_services' => 'boolean',
    ];

    // Relationships
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function courseRef(): BelongsTo
    {
        return $this->belongsTo(SisCourseRef::class, 'course_reg_no', 'course_reg_no');
    }

    // Scopes
    public function scopeForTerm($query, $termCode)
    {
        return $query->where('term_code', $termCode);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeServices($query)
    {
        return $query->where('applies_to_services', true);
    }
}
