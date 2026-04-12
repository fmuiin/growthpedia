<?php

declare(strict_types=1);

use App\Shared\Exceptions\AuthorizationException;
use App\Shared\Exceptions\BusinessException;
use App\Shared\Exceptions\EntityNotFoundException;
use App\Shared\Exceptions\ValidationException;

it('carries a status code', function () {
    $e = new BusinessException('Something went wrong', 400);

    expect($e->getMessage())->toBe('Something went wrong')
        ->and($e->statusCode)->toBe(400)
        ->and($e)->toBeInstanceOf(RuntimeException::class);
});

it('EntityNotFoundException defaults to 404', function () {
    $e = new EntityNotFoundException();

    expect($e->statusCode)->toBe(404)
        ->and($e->getMessage())->toBe('Entity not found.');
});

it('ValidationException defaults to 422', function () {
    $e = new ValidationException('Bad input');

    expect($e->statusCode)->toBe(422)
        ->and($e->getMessage())->toBe('Bad input');
});

it('AuthorizationException defaults to 403', function () {
    $e = new AuthorizationException();

    expect($e->statusCode)->toBe(403)
        ->and($e->getMessage())->toBe('Unauthorized.');
});

it('all exceptions extend BusinessException', function () {
    expect(new EntityNotFoundException())->toBeInstanceOf(BusinessException::class)
        ->and(new ValidationException())->toBeInstanceOf(BusinessException::class)
        ->and(new AuthorizationException())->toBeInstanceOf(BusinessException::class);
});
