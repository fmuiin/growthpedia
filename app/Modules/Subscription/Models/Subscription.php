<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'membership_plan_id',
        'status',
        'starts_at',
        'ends_at',
        'grace_period_ends_at',
        'cancelled_at',
        'gateway_subscription_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'grace_period_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function membershipPlan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class);
    }
}
