<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialTemplateVersion extends Model
{
    protected $fillable = [
        'template_id',
        'version',
        'name',
        'design_json',
        'svg',
        'preview_path',
        'changelog',
        'quality_report',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'design_json' => 'array',
            'quality_report' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SocialTemplate::class, 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
