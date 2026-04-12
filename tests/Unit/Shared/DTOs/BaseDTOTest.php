<?php

declare(strict_types=1);

use App\Shared\DTOs\BaseDTO;

final readonly class ConcreteDTO extends BaseDTO
{
    public function __construct(
        public string $name,
        public int $value,
    ) {}
}

it('converts to array with all properties', function () {
    $dto = new ConcreteDTO(name: 'test', value: 42);

    expect($dto->toArray())->toBe(['name' => 'test', 'value' => 42]);
});
