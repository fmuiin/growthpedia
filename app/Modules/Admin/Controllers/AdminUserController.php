<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admin\DTOs\AdminUserDTO;
use App\Modules\Admin\DTOs\PaginatedAdminUsersDTO;
use App\Modules\Admin\Requests\AssignRoleRequest;
use App\Modules\Admin\Requests\SuspendUserRequest;
use App\Modules\Subscription\Models\Subscription;
use App\Modules\User\Contracts\UserServiceInterface;
use App\Modules\User\Exceptions\LastAdminProtectionException;
use App\Shared\Exceptions\EntityNotFoundException;
use App\Shared\Exceptions\ValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    private const int PER_PAGE = 15;

    public function __construct(
        private readonly UserServiceInterface $userService,
    ) {}

    /**
     * Display a paginated list of all registered users with roles,
     * subscription status, and registration date.
     *
     * Validates: Requirement 8.1
     */
    public function index(Request $request): Response
    {
        $page = (int) $request->query('page', '1');

        $paginatedUsers = $this->getPaginatedUsers($page);

        return Inertia::render('Admin/UserManagement', [
            'users' => $paginatedUsers->toArray(),
        ]);
    }

    /**
     * Assign a role (Learner, Instructor, Admin) to a user.
     * Permissions update immediately.
     *
     * Validates: Requirement 8.2
     */
    public function assignRole(int $userId, AssignRoleRequest $request): RedirectResponse
    {
        try {
            $this->userService->assignRole($userId, $request->validated('role'));
        } catch (ValidationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'User not found.');
        }

        return redirect()->back()->with('success', 'User role updated successfully.');
    }

    /**
     * Suspend a user account — revoke access, display suspension notice.
     * Cannot suspend the last remaining admin account.
     *
     * Validates: Requirements 8.3, 8.5
     */
    public function suspend(int $userId, SuspendUserRequest $request): RedirectResponse
    {
        try {
            $this->userService->suspendUser($userId);
        } catch (LastAdminProtectionException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'User not found.');
        }

        return redirect()->back()->with('success', 'User has been suspended.');
    }

    /**
     * Search users by name or email.
     *
     * Validates: Requirement 8.4
     */
    public function search(Request $request): Response
    {
        $query = (string) $request->query('q', '');
        $page = (int) $request->query('page', '1');

        if ($query === '') {
            return $this->index($request);
        }

        $result = $this->userService->searchUsers($query, $page);

        // Map UserDTOs to AdminUserDTOs with subscription status
        $userIds = array_map(fn ($u) => $u->id, $result->users);
        $subscriptionStatuses = $this->getSubscriptionStatuses($userIds);

        $adminUsers = array_map(
            fn ($userDto) => new AdminUserDTO(
                id: $userDto->id,
                name: $userDto->name,
                email: $userDto->email,
                role: $userDto->role,
                subscriptionStatus: $subscriptionStatuses[$userDto->id] ?? null,
                registrationDate: $userDto->created_at,
                isSuspended: $userDto->is_suspended,
            ),
            $result->users,
        );

        $paginatedResult = new PaginatedAdminUsersDTO(
            users: $adminUsers,
            total: $result->total,
            currentPage: $result->currentPage,
            perPage: $result->perPage,
            lastPage: (int) ceil($result->total / max($result->perPage, 1)),
        );

        return Inertia::render('Admin/UserManagement', [
            'users' => $paginatedResult->toArray(),
            'searchQuery' => $query,
        ]);
    }

    /**
     * Build a paginated list of users with subscription status.
     */
    private function getPaginatedUsers(int $page): PaginatedAdminUsersDTO
    {
        $paginator = User::orderByDesc('created_at')
            ->paginate(self::PER_PAGE, ['*'], 'page', $page);

        /** @var User[] $users */
        $users = $paginator->items();
        $userIds = array_map(fn (User $u) => $u->id, $users);
        $subscriptionStatuses = $this->getSubscriptionStatuses($userIds);

        $adminUsers = array_map(
            fn (User $user) => new AdminUserDTO(
                id: $user->id,
                name: $user->name,
                email: $user->email,
                role: $user->role,
                subscriptionStatus: $subscriptionStatuses[$user->id] ?? null,
                registrationDate: $user->created_at,
                isSuspended: $user->is_suspended,
            ),
            $users,
        );

        return new PaginatedAdminUsersDTO(
            users: $adminUsers,
            total: $paginator->total(),
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            lastPage: $paginator->lastPage(),
        );
    }

    /**
     * Get the latest subscription status for a set of user IDs.
     *
     * @param int[] $userIds
     * @return array<int, string> Map of user_id => subscription status
     */
    private function getSubscriptionStatuses(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        // Get the most recent subscription per user
        $latestSubscriptions = Subscription::select('user_id', 'status')
            ->whereIn('user_id', $userIds)
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('subscriptions')
                    ->groupBy('user_id');
            })
            ->get();

        $statuses = [];
        foreach ($latestSubscriptions as $sub) {
            $statuses[$sub->user_id] = $sub->status;
        }

        return $statuses;
    }
}
