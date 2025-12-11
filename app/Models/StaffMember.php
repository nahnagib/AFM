<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffMember extends Model
{
    protected $fillable = ['staff_role_id', 'name_ar', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(StaffRole::class, 'staff_role_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
