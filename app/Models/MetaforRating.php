<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['metafor_id', 'rater_token', 'rating'])]
class MetaforRating extends Model
{
    public function metafor(): BelongsTo
    {
        return $this->belongsTo(Metafor::class);
    }
}