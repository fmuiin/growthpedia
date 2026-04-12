<?php

declare(strict_types=1);

namespace App\Modules\User\Contracts;

use App\Modules\User\DTOs\LoginDTO;
use App\Modules\User\DTOs\PaginatedUsersDTO;
use App\Modules\User\DTOs\RegisterDTO;
use App\Modules\User\DTOs\ResetPasswordDTO;
use App\Modules\User\DTOs\UserDTO;
use App\Shared\Contracts\ServiceInterface;

interface UserServiceInterface extends ServiceInterface
{
    public function register(RegisterDTO $dto): UserDTO;

    public function verifyEmail(int $userId): bool;

    public function attemptLogin(LoginDTO $dto): UserDTO;

    public function assignRole(int $userId, string $role): UserDTO;

    public function suspendUser(int $userId): void;

    public function searchUsers(string $query, int $page = 1): PaginatedUsersDTO;

    public function lockAccount(int $userId, int $minutes): void;

    public function requestPasswordReset(string $email): void;

    public function resetPassword(ResetPasswordDTO $dto): void;
}
