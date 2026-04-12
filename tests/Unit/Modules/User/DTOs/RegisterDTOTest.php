<?php

declare(strict_types=1);

use App\Modules\User\DTOs\RegisterDTO;
use App\Shared\DTOs\BaseDTO;

it('extends BaseDTO', function () {
    $dto = new RegisterDTO(name: 'John', email: 'john@example.com', password: 'secret123');

    expect($dto)->toBeInstanceOf(BaseDTO::class);
});

it('stores all properties as readonly', function () {
    $dto = new RegisterDTO(name: 'John', email: 'john@example.com', password: 'secret123');

    expect($dto->name)->toBe('John')
        ->and($dto->email)->toBe('john@example.com')
        ->and($dto->password)->toBe('secret123');
});

it('converts to array with all fields', function () {
    $dto = new RegisterDTO(name: 'John', email: 'john@example.com', password: 'secret123');

    expect($dto->toArray())->toBe([
        'name' => 'John',
        'email' => 'john@example.com',
        'password' => 'secret123',
    ]);
});
