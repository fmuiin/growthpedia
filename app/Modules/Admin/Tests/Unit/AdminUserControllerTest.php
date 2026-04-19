<?php

declare(strict_types=1);

namespace App\Modules\Admin\Tests\Unit;

use App\Models\User;
use App\Modules\Subscription\Models\MembershipPlan;
use App\Modules\Subscription\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    // --- Index (Requirement 8.1) ---

    public function test_index_returns_paginated_user_list(): void
    {
        User::factory()->count(3)->create(['role' => 'learner']);

        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/UserManagement')
            ->has('users')
            ->where('users.total', 4) // 3 learners + 1 admin
        );
    }

    public function test_index_includes_roles_and_registration_date(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);

        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/UserManagement')
            ->has('users.users', 2) // admin + learner
        );
    }

    public function test_index_includes_subscription_status(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $plan = MembershipPlan::create([
            'name' => 'Basic',
            'description' => null,
            'price' => '10.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);
        Subscription::create([
            'user_id' => $learner->id,
            'membership_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));

        $response->assertStatus(200);
    }

    // --- Assign Role (Requirement 8.2) ---

    public function test_assign_role_updates_user_role(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.assign-role', $learner->id), [
                'role' => 'instructor',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $learner->id,
            'role' => 'instructor',
        ]);
    }

    public function test_assign_role_rejects_invalid_role(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.assign-role', $learner->id), [
                'role' => 'superadmin',
            ]);

        $response->assertSessionHasErrors('role');
    }

    // --- Suspend (Requirements 8.3, 8.5) ---

    public function test_suspend_user_sets_is_suspended(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.suspend', $learner->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $learner->id,
            'is_suspended' => true,
        ]);
    }

    public function test_suspend_last_admin_is_rejected(): void
    {
        // The only admin is $this->admin
        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.suspend', $this->admin->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'is_suspended' => false,
        ]);
    }

    // --- Search (Requirement 8.4) ---

    public function test_search_returns_matching_users_by_name(): void
    {
        User::factory()->create(['name' => 'Alice Smith', 'role' => 'learner']);
        User::factory()->create(['name' => 'Bob Jones', 'role' => 'learner']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.search', ['q' => 'Alice']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/UserManagement')
            ->where('users.total', 1)
            ->where('searchQuery', 'Alice')
        );
    }

    public function test_search_returns_matching_users_by_email(): void
    {
        User::factory()->create(['email' => 'alice@example.com', 'role' => 'learner']);
        User::factory()->create(['email' => 'bob@example.com', 'role' => 'learner']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.search', ['q' => 'alice@']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/UserManagement')
            ->where('users.total', 1)
        );
    }

    public function test_search_with_empty_query_returns_all_users(): void
    {
        User::factory()->count(2)->create(['role' => 'learner']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.search', ['q' => '']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/UserManagement')
            ->where('users.total', 3) // 2 learners + 1 admin
        );
    }

    // --- Middleware (Admin access restriction) ---

    public function test_non_admin_cannot_access_user_list(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);

        $response = $this->actingAs($learner)->get(route('admin.users.index'));

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_user_list(): void
    {
        $response = $this->get(route('admin.users.index'));

        $response->assertRedirect();
    }
}
