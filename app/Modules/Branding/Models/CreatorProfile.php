<?php

declare(strict_types=1);

namespace App\Modules\Branding\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorProfile extends Model
{
    protected $fillable = [
        'user_id',
        'display_name',
        'bio',
        'avatar_url',
        'expertise',
        'social_links',
        'featured_course_ids',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'featured_course_ids' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
