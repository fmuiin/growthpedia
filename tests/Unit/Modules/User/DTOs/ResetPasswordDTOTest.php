<?php

declare(strict_types=1);

use App\Modules\User\DTOs\ResetPasswordDTO;
use App\Shared\DTOs\BaseDTO;

it('extends BaseDTO', function () {
    $dto = new ResetPasswordDTO(email: 'john@example.com', token: 'abc123', password: 'newpass');

    expect($dto)->toBeInstanceOf(BaseDTO::class);
});

it('stores all properties as readonly', function () {
    $dto = new ResetPasswordDTO(email: 'john@example.com', token: 'abc123', password: 'newpass');

    expect($dto->email)->toBe('john@example.com')
        ->and($dto->token)->toBe('abc123')
        ->and($dto->password)->toBe('newpass');
});

it('converts to array with all fields', function () {
    $dto = new ResetPasswordDTO(email: 'john@example.com', token: 'abc123', password: 'newpass');

    expect($dto->toArray())->toBe([
        'email' => 'john@example.com',
        'token' => 'abc123',
        'password' => 'newpass',
    ]);
});
