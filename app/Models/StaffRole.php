<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffRole extends Model
{
    protected $fillable = ['role_key', 'label_ar', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function staffMembers(): HasMany
    {
        return $this->hasMany(StaffMember::class);
    }
}
