<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Form extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_published' => 'boolean',
        'is_anonymous' => 'boolean',
        'version' => 'integer',
        'estimated_minutes' => 'integer',
    ];

    // Relationships
    public function sections(): HasMany
    {
        return $this->hasMany(FormSection::class)->orderBy('order');
    }

    public function questions(): HasManyThrough
    {
        return $this->hasManyThrough(Question::class, FormSection::class, 'form_id', 'section_id')
            ->orderBy('form_sections.order')
            ->orderBy('questions.order');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    public function completionFlags(): HasMany
    {
        return $this->hasMany(CompletionFlag::class);
    }

    public function courseScopes(): HasMany
    {
        return $this->hasMany(FormCourseScope::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeCourseFeedback($query)
    {
        return $query->where('form_type', 'course_feedback');
    }

    public function scopeSystemServices($query)
    {
        return $query->where('form_type', 'system_services');
    }

    // Accessors
    public function getIsEditableAttribute(): bool
    {
        // Can edit if no responses yet
        return $this->responses()->count() === 0;
    }

    public function getHasResponsesAttribute(): bool
    {
        return $this->responses()->count() > 0;
    }
}
