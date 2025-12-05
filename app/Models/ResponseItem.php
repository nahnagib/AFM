<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResponseItem extends Model
{
    protected $guarded = ['id'];

    // Relationships
    public function response(): BelongsTo
    {
        return $this->belongsTo(Response::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    // Accessors
    public function getValueAttribute()
    {
        if (!is_null($this->numeric_value)) {
            return $this->numeric_value;
        }
        if (!is_null($this->option_value)) {
            return $this->option_value;
        }
        return $this->text_value;
    }
}
