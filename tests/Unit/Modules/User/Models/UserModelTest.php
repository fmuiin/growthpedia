<?php

declare(strict_types=1);

use App\Models\User;

it('has the expected fillable attributes', function () {
    $user = new User();

    expect($user->getFillable())->toBe([
        'name',
        'email',
        'password',
        'role',
        'is_suspended',
        'failed_login_attempts',
        'locked_until',
    ]);
});

it('has the expected hidden attributes', function () {
    $user = new User();

    expect($user->getHidden())->toBe([
        'password',
        'remember_token',
    ]);
});

it('casts is_suspended to boolean', function () {
    $user = new User();
    $casts = $user->getCasts();

    expect($casts['is_suspended'])->toBe('boolean');
});

it('casts failed_login_attempts to integer', function () {
    $user = new User();
    $casts = $user->getCasts();

    expect($casts['failed_login_attempts'])->toBe('integer');
});

it('casts locked_until to datetime', function () {
    $user = new User();
    $casts = $user->getCasts();

    expect($casts['locked_until'])->toBe('datetime');
});

it('casts email_verified_at to datetime', function () {
    $user = new User();
    $casts = $user->getCasts();

    expect($casts['email_verified_at'])->toBe('datetime');
});

it('casts password as hashed', function () {
    $user = new User();
    $casts = $user->getCasts();

    expect($casts['password'])->toBe('hashed');
});
