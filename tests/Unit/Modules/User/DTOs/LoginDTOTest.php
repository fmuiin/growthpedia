<?php

declare(strict_types=1);

use App\Modules\User\DTOs\LoginDTO;
use App\Shared\DTOs\BaseDTO;

it('extends BaseDTO', function () {
    $dto = new LoginDTO(email: 'john@example.com', password: 'secret123');

    expect($dto)->toBeInstanceOf(BaseDTO::class);
});

it('stores all properties as readonly', function () {
    $dto = new LoginDTO(email: 'john@example.com', password: 'secret123');

    expect($dto->email)->toBe('john@example.com')
        ->and($dto->password)->toBe('secret123');
});

it('converts to array with all fields', function () {
    $dto = new LoginDTO(email: 'john@example.com', password: 'secret123');

    expect($dto->toArray())->toBe([
        'email' => 'john@example.com',
        'password' => 'secret123',
    ]);
});
