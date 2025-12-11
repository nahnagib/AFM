<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'required' => 'boolean',
        'allow_na' => 'boolean',
        'scale_min' => 'integer',
        'scale_max' => 'integer',
        'max_length' => 'integer',
        'order' => 'integer',
    ];

    // Relationships
    public function section(): BelongsTo
    {
        return $this->belongsTo(FormSection::class, 'section_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order');
    }

    public function responseItems(): HasMany
    {
        return $this->hasMany(ResponseItem::class);
    }

    public function staffRole(): BelongsTo
    {
        return $this->belongsTo(StaffRole::class, 'staff_role_id');
    }

    // Scopes
    public function scopeRequired($query)
    {
        return $query->where('required', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('qtype', $type);
    }

    // Accessors
    public function getIsLikertAttribute(): bool
    {
        return $this->qtype === 'likert';
    }

    public function getIsMcqAttribute(): bool
    {
        return in_array($this->qtype, ['mcq_single', 'mcq_multi']);
    }

    public function getIsTextAttribute(): bool
    {
        return in_array($this->qtype, ['text', 'textarea']);
    }

    public function getIsStaffSelectionAttribute(): bool
    {
        return !is_null($this->staff_role_id);
    }
}
