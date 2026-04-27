<?php

declare(strict_types=1);

namespace App\Modules\Branding\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPageSection extends Model
{
    protected $fillable = [
        'section_type',
        'title',
        'subtitle',
        'content',
        'image_url',
        'cta_text',
        'cta_url',
        'sort_order',
        'is_visible',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_visible' => 'boolean',
            'metadata' => 'array',
        ];
    }
}
