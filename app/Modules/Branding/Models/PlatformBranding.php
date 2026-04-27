<?php

declare(strict_types=1);

namespace App\Modules\Branding\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformBranding extends Model
{
    protected $fillable = [
        'site_name',
        'tagline',
        'logo_url',
        'favicon_url',
        'primary_color',
        'secondary_color',
        'footer_text',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
