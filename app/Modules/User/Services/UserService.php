<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Models\User;
use App\Modules\User\Contracts\UserServiceInterface;
use App\Modules\User\DTOs\LoginDTO;
use App\Modules\User\DTOs\PaginatedUsersDTO;
use App\Modules\User\DTOs\RegisterDTO;
use App\Modules\User\DTOs\ResetPasswordDTO;
use App\Modules\User\DTOs\UserDTO;
use App\Modules\User\Events\AccountLocked;
use App\Modules\User\Events\UserRegistered;
use App\Modules\User\Exceptions\AccountLockedException;
use App\Modules\User\Exceptions\InvalidCredentialsException;
use App\Modules\User\Exceptions\LastAdminProtectionException;
use App\Modules\User\Exceptions\VerificationExpiredException;
use App\Shared\Exceptions\EntityNotFoundException;
use App\Shared\Exceptions\ValidationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class UserService implements UserServiceInterface
{
    private const int MAX_FAILED_ATTEMPTS = 5;
    private const int LOCKOUT_WINDOW_MINUTES = 15;
    private const int LOCKOUT_DURATION_MINUTES = 30;
    private const int EMAIL_VERIFICATION_HOURS = 24;
    private const int PASSWORD_RESET_MINUTES = 60;
    private const int SEARCH_PER_PAGE = 15;
    private const array VALID_ROLES = ['learner', 'instructor', 'admin'];

    public function register(RegisterDTO $dto): UserDTO
    {
        $user = User::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $dto->password,
            'role' => 'learner',
            'is_suspended' => false,
            'failed_login_attempts' => 0,
        ]);

        event(new UserRegistered($user->id));

        return $this->toUserDTO($user);
    }

    public function verifyEmail(int $userId): bool
    {
        $user = User::findOrFail($userId);

        if ($user->email_verified_at !== null) {
            return true;
        }

        if ($user->created_at->diffInHours(Carbon::now()) >= self::EMAIL_VERIFICATION_HOURS) {
            throw new VerificationExpiredException();
        }

        $user->email_verified_at = Carbon::now();
        $user->save();

        return true;
    }

    public function attemptLogin(LoginDTO $dto): UserDTO
    {
        $user = User::where('email', $dto->email)->first();

        if ($user === null) {
            throw new InvalidCredentialsException();
        }

        if ($this->isAccountLocked($user)) {
            throw new AccountLockedException();
        }

        if ($user->is_suspended) {
            throw new InvalidCredentialsException();
        }

        if (!Hash::check($dto->password, $user->password)) {
            $this->handleFailedLogin($user);
            throw new InvalidCredentialsException();
        }

        // Successful login — reset failed attempts
        $user->failed_login_attempts = 0;
        $user->save();

        return $this->toUserDTO($user);
    }

    public function assignRole(int $userId, string $role): UserDTO
    {
        if (!in_array($role, self::VALID_ROLES, true)) {
            throw new ValidationException("Invalid role: {$role}. Must be one of: " . implode(', ', self::VALID_ROLES));
        }

        $user = User::findOrFail($userId);
        $user->role = $role;
        $user->save();

        return $this->toUserDTO($user);
    }

    public function suspendUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->role === 'admin') {
            $adminCount = User::where('role', 'admin')
                ->where('is_suspended', false)
                ->count();

            if ($adminCount <= 1) {
                throw new LastAdminProtectionException();
            }
        }

        $user->is_suspended = true;
        $user->save();
    }

    public function searchUsers(string $query, int $page = 1): PaginatedUsersDTO
    {
        $paginator = User::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('email', 'like', "%{$query}%");
        })
        ->orderBy('name')
        ->paginate(self::SEARCH_PER_PAGE, ['*'], 'page', $page);

        $users = collect($paginator->items())->map(
            fn (User $user) => $this->toUserDTO($user)
        )->all();

        return new PaginatedUsersDTO(
            users: $users,
            total: $paginator->total(),
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
        );
    }

    public function lockAccount(int $userId, int $minutes): void
    {
        $user = User::findOrFail($userId);
        $lockedUntil = Carbon::now()->addMinutes($minutes);

        $user->locked_until = $lockedUntil;
        $user->save();

        event(new AccountLocked($user->id, $lockedUntil));
    }

    public function requestPasswordReset(string $email): void
    {
        $user = User::where('email', $email)->first();

        if ($user === null) {
            // Silently return to avoid revealing whether the email exists
            return;
        }

        Password::sendResetLink(['email' => $email]);
    }

    public function resetPassword(ResetPasswordDTO $dto): void
    {
        $status = Password::reset(
            [
                'email' => $dto->email,
                'password' => $dto->password,
                'password_confirmation' => $dto->password,
                'token' => $dto->token,
            ],
            function (User $user, string $password) {
                $user->password = $password;
                $user->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new ValidationException('Invalid or expired password reset token.');
        }
    }

    private function isAccountLocked(User $user): bool
    {
        return $user->locked_until !== null && Carbon::now()->lt($user->locked_until);
    }

    private function handleFailedLogin(User $user): void
    {
        $user->failed_login_attempts = $user->failed_login_attempts + 1;
        $user->save();

        if ($user->failed_login_attempts >= self::MAX_FAILED_ATTEMPTS) {
            $this->lockAccount($user->id, self::LOCKOUT_DURATION_MINUTES);
            $user->failed_login_attempts = 0;
            $user->save();
        }
    }

    private function toUserDTO(User $user): UserDTO
    {
        return new UserDTO(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            role: $user->role,
            email_verified_at: $user->email_verified_at,
            is_suspended: $user->is_suspended,
            created_at: $user->created_at,
        );
    }
}
