<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\User\DTOs\LoginDTO;
use App\Modules\User\DTOs\RegisterDTO;
use App\Modules\User\DTOs\ResetPasswordDTO;
use App\Modules\User\DTOs\UserDTO;
use App\Modules\User\Events\AccountLocked;
use App\Modules\User\Events\UserRegistered;
use App\Modules\User\Exceptions\AccountLockedException;
use App\Modules\User\Exceptions\InvalidCredentialsException;
use App\Modules\User\Exceptions\LastAdminProtectionException;
use App\Modules\User\Exceptions\VerificationExpiredException;
use App\Modules\User\Services\UserService;
use App\Shared\Exceptions\ValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new UserService();
});

// --- Registration ---

test('register creates a learner account and dispatches UserRegistered event', function () {
    Event::fake([UserRegistered::class]);

    $dto = new RegisterDTO(name: 'John Doe', email: 'john@example.com', password: 'secret123');
    $result = $this->service->register($dto);

    expect($result)->toBeInstanceOf(UserDTO::class)
        ->and($result->name)->toBe('John Doe')
        ->and($result->email)->toBe('john@example.com')
        ->and($result->role)->toBe('learner')
        ->and($result->is_suspended)->toBeFalse()
        ->and($result->email_verified_at)->toBeNull();

    $user = User::find($result->id);
    expect($user)->not->toBeNull()
        ->and(Hash::check('secret123', $user->password))->toBeTrue();

    Event::assertDispatched(UserRegistered::class, fn ($e) => $e->userId === $result->id);
});

// --- Email Verification ---

test('verifyEmail activates account within 24-hour window', function () {
    $user = User::factory()->unverified()->create();

    $result = $this->service->verifyEmail($user->id);

    expect($result)->toBeTrue();

    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull();
});

test('verifyEmail returns true for already verified account', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $result = $this->service->verifyEmail($user->id);

    expect($result)->toBeTrue();
});

test('verifyEmail throws VerificationExpiredException after 24 hours', function () {
    $user = User::factory()->unverified()->create([
        'created_at' => Carbon::now()->subHours(25),
    ]);

    $this->service->verifyEmail($user->id);
})->throws(VerificationExpiredException::class);

// --- Login ---

test('attemptLogin succeeds with valid credentials', function () {
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
        'role' => 'learner',
    ]);

    $dto = new LoginDTO(email: $user->email, password: 'correct-password');
    $result = $this->service->attemptLogin($dto);

    expect($result)->toBeInstanceOf(UserDTO::class)
        ->and($result->id)->toBe($user->id);
});

test('attemptLogin resets failed attempts on success', function () {
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
        'failed_login_attempts' => 3,
    ]);

    $dto = new LoginDTO(email: $user->email, password: 'correct-password');
    $this->service->attemptLogin($dto);

    $user->refresh();
    expect($user->failed_login_attempts)->toBe(0);
});

test('attemptLogin throws InvalidCredentialsException for wrong password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
    ]);

    $dto = new LoginDTO(email: $user->email, password: 'wrong-password');
    $this->service->attemptLogin($dto);
})->throws(InvalidCredentialsException::class);

test('attemptLogin throws InvalidCredentialsException for non-existent email', function () {
    $dto = new LoginDTO(email: 'nobody@example.com', password: 'anything');
    $this->service->attemptLogin($dto);
})->throws(InvalidCredentialsException::class);

test('attemptLogin increments failed attempts on wrong password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
        'failed_login_attempts' => 0,
    ]);

    $dto = new LoginDTO(email: $user->email, password: 'wrong');

    try {
        $this->service->attemptLogin($dto);
    } catch (InvalidCredentialsException) {
    }

    $user->refresh();
    expect($user->failed_login_attempts)->toBe(1);
});

test('attemptLogin locks account after 5 failed attempts', function () {
    Event::fake([AccountLocked::class]);

    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
        'failed_login_attempts' => 4,
    ]);

    $dto = new LoginDTO(email: $user->email, password: 'wrong');

    try {
        $this->service->attemptLogin($dto);
    } catch (InvalidCredentialsException) {
    }

    $user->refresh();
    expect($user->locked_until)->not->toBeNull()
        ->and($user->failed_login_attempts)->toBe(0);

    Event::assertDispatched(AccountLocked::class);
});

test('attemptLogin throws AccountLockedException when account is locked', function () {
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
        'locked_until' => Carbon::now()->addMinutes(30),
    ]);

    $dto = new LoginDTO(email: $user->email, password: 'correct-password');
    $this->service->attemptLogin($dto);
})->throws(AccountLockedException::class);

test('attemptLogin throws InvalidCredentialsException for suspended user', function () {
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
        'is_suspended' => true,
    ]);

    $dto = new LoginDTO(email: $user->email, password: 'correct-password');
    $this->service->attemptLogin($dto);
})->throws(InvalidCredentialsException::class);

// --- Lock Account ---

test('lockAccount sets locked_until and dispatches event', function () {
    Event::fake([AccountLocked::class]);

    $user = User::factory()->create();

    $this->service->lockAccount($user->id, 30);

    $user->refresh();
    expect($user->locked_until)->not->toBeNull();

    Event::assertDispatched(AccountLocked::class, fn ($e) => $e->userId === $user->id);
});

// --- Suspend User ---

test('suspendUser sets is_suspended to true', function () {
    $user = User::factory()->create(['role' => 'learner', 'is_suspended' => false]);

    $this->service->suspendUser($user->id);

    $user->refresh();
    expect($user->is_suspended)->toBeTrue();
});

test('suspendUser throws LastAdminProtectionException for last admin', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_suspended' => false]);

    $this->service->suspendUser($admin->id);
})->throws(LastAdminProtectionException::class);

test('suspendUser allows suspending admin when another admin exists', function () {
    User::factory()->create(['role' => 'admin', 'is_suspended' => false]);
    $admin2 = User::factory()->create(['role' => 'admin', 'is_suspended' => false]);

    $this->service->suspendUser($admin2->id);

    $admin2->refresh();
    expect($admin2->is_suspended)->toBeTrue();
});

// --- Assign Role ---

test('assignRole updates user role', function () {
    $user = User::factory()->create(['role' => 'learner']);

    $result = $this->service->assignRole($user->id, 'instructor');

    expect($result->role)->toBe('instructor');

    $user->refresh();
    expect($user->role)->toBe('instructor');
});

test('assignRole throws ValidationException for invalid role', function () {
    $user = User::factory()->create();

    $this->service->assignRole($user->id, 'superadmin');
})->throws(ValidationException::class);

// --- Search Users ---

test('searchUsers returns matching users by name', function () {
    User::factory()->create(['name' => 'Alice Smith']);
    User::factory()->create(['name' => 'Bob Jones']);
    User::factory()->create(['name' => 'Alice Wonder']);

    $result = $this->service->searchUsers('Alice');

    expect($result->total)->toBe(2)
        ->and($result->users)->toHaveCount(2);
});

test('searchUsers returns matching users by email', function () {
    User::factory()->create(['email' => 'alice@example.com']);
    User::factory()->create(['email' => 'bob@example.com']);

    $result = $this->service->searchUsers('alice@');

    expect($result->total)->toBe(1)
        ->and($result->users[0]->email)->toBe('alice@example.com');
});

test('searchUsers returns empty for no matches', function () {
    User::factory()->create(['name' => 'Alice', 'email' => 'alice@example.com']);

    $result = $this->service->searchUsers('zzzzz');

    expect($result->total)->toBe(0)
        ->and($result->users)->toHaveCount(0);
});
