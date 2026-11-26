<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    protected $fillable = ['user_id', 'user_design_id', 'generated_logo_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(UserDesign::class, 'user_design_id');
    }

    public function generatedLogo(): BelongsTo
    {
        return $this->belongsTo(GeneratedLogo::class);
    }
}
