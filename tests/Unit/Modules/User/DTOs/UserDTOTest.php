<?php

declare(strict_types=1);

use App\Modules\User\DTOs\UserDTO;
use App\Shared\DTOs\BaseDTO;
use Carbon\CarbonImmutable;

it('extends BaseDTO', function () {
    $now = CarbonImmutable::now();

    $dto = new UserDTO(
        id: 1,
        name: 'John',
        email: 'john@example.com',
        role: 'learner',
        email_verified_at: $now,
        is_suspended: false,
        created_at: $now,
    );

    expect($dto)->toBeInstanceOf(BaseDTO::class);
});

it('stores all properties including nullable email_verified_at', function () {
    $now = CarbonImmutable::now();

    $dto = new UserDTO(
        id: 5,
        name: 'Jane',
        email: 'jane@example.com',
        role: 'instructor',
        email_verified_at: null,
        is_suspended: false,
        created_at: $now,
    );

    expect($dto->id)->toBe(5)
        ->and($dto->name)->toBe('Jane')
        ->and($dto->email)->toBe('jane@example.com')
        ->and($dto->role)->toBe('instructor')
        ->and($dto->email_verified_at)->toBeNull()
        ->and($dto->is_suspended)->toBeFalse()
        ->and($dto->created_at)->toBe($now);
});

it('converts to array with all fields', function () {
    $now = CarbonImmutable::now();

    $dto = new UserDTO(
        id: 1,
        name: 'John',
        email: 'john@example.com',
        role: 'admin',
        email_verified_at: $now,
        is_suspended: true,
        created_at: $now,
    );

    expect($dto->toArray())->toBe([
        'id' => 1,
        'name' => 'John',
        'email' => 'john@example.com',
        'role' => 'admin',
        'email_verified_at' => $now,
        'is_suspended' => true,
        'created_at' => $now,
    ]);
});
