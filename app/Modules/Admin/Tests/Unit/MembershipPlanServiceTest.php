<?php

declare(strict_types=1);

namespace App\Modules\Admin\Tests\Unit;

use App\Models\User;
use App\Modules\Admin\DTOs\CreateMembershipPlanDTO;
use App\Modules\Admin\DTOs\UpdateMembershipPlanDTO;
use App\Modules\Admin\Exceptions\PlanHasActiveSubscriptionsException;
use App\Modules\Admin\Services\MembershipPlanService;
use App\Modules\Course\Models\Course;
use App\Modules\Subscription\DTOs\MembershipPlanDTO;
use App\Modules\Subscription\Models\MembershipPlan;
use App\Modules\Subscription\Models\Subscription;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipPlanServiceTest extends TestCase
{
    use RefreshDatabase;

    private MembershipPlanService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MembershipPlanService();
    }

    public function test_create_plan_returns_membership_plan_dto(): void
    {
        $dto = new CreateMembershipPlanDTO(
            name: 'Basic Plan',
            description: 'Access to basic courses',
            price: '99.99',
            billingFrequency: 'monthly',
            courseIds: [],
        );

        $result = $this->service->createPlan($dto);

        $this->assertInstanceOf(MembershipPlanDTO::class, $result);
        $this->assertEquals('Basic Plan', $result->name);
        $this->assertEquals('Access to basic courses', $result->description);
        $this->assertEquals('99.99', $result->price);
        $this->assertEquals('monthly', $result->billingFrequency);
        $this->assertTrue($result->isActive);
    }

    public function test_create_plan_persists_to_database(): void
    {
        $dto = new CreateMembershipPlanDTO(
            name: 'Pro Plan',
            description: 'All courses',
            price: '199.00',
            billingFrequency: 'yearly',
        );

        $result = $this->service->createPlan($dto);

        $this->assertDatabaseHas('membership_plans', [
            'id' => $result->id,
            'name' => 'Pro Plan',
            'billing_frequency' => 'yearly',
            'is_active' => true,
        ]);
    }

    public function test_create_plan_syncs_course_ids(): void
    {
        $user = User::factory()->create();
        $course1 = Course::create([
            'instructor_id' => $user->id,
            'title' => 'Course 1',
            'description' => 'Desc',
            'category' => 'Cat',
            'status' => 'published',
        ]);
        $course2 = Course::create([
            'instructor_id' => $user->id,
            'title' => 'Course 2',
            'description' => 'Desc',
            'category' => 'Cat',
            'status' => 'published',
        ]);

        $dto = new CreateMembershipPlanDTO(
            name: 'Bundle',
            description: null,
            price: '149.00',
            billingFrequency: 'monthly',
            courseIds: [$course1->id, $course2->id],
        );

        $result = $this->service->createPlan($dto);

        $plan = MembershipPlan::find($result->id);
        $this->assertCount(2, $plan->courses);
        $this->assertTrue($plan->courses->contains($course1));
        $this->assertTrue($plan->courses->contains($course2));
    }

    public function test_update_plan_updates_only_non_null_fields(): void
    {
        $plan = MembershipPlan::create([
            'name' => 'Original',
            'description' => 'Original Desc',
            'price' => '50.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $result = $this->service->updatePlan($plan->id, new UpdateMembershipPlanDTO(
            name: 'Updated Name',
        ));

        $this->assertEquals('Updated Name', $result->name);
        $this->assertEquals('Original Desc', $result->description);
        $this->assertEquals('50.00', $result->price);
        $this->assertEquals('monthly', $result->billingFrequency);
    }

    public function test_update_plan_syncs_courses_when_provided(): void
    {
        $user = User::factory()->create();
        $course = Course::create([
            'instructor_id' => $user->id,
            'title' => 'New Course',
            'description' => 'Desc',
            'category' => 'Cat',
            'status' => 'published',
        ]);

        $plan = MembershipPlan::create([
            'name' => 'Plan',
            'description' => null,
            'price' => '100.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $this->service->updatePlan($plan->id, new UpdateMembershipPlanDTO(
            courseIds: [$course->id],
        ));

        $plan->refresh();
        $this->assertCount(1, $plan->courses);
        $this->assertTrue($plan->courses->contains($course));
    }

    public function test_update_plan_throws_when_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->service->updatePlan(999, new UpdateMembershipPlanDTO(name: 'X'));
    }

    public function test_deactivate_plan_sets_is_active_to_false(): void
    {
        $plan = MembershipPlan::create([
            'name' => 'Active Plan',
            'description' => null,
            'price' => '75.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $result = $this->service->deactivatePlan($plan->id);

        $this->assertFalse($result->isActive);
        $this->assertDatabaseHas('membership_plans', [
            'id' => $plan->id,
            'is_active' => false,
        ]);
    }

    public function test_deactivate_plan_throws_when_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->service->deactivatePlan(999);
    }

    public function test_delete_plan_removes_plan_from_database(): void
    {
        $plan = MembershipPlan::create([
            'name' => 'To Delete',
            'description' => null,
            'price' => '10.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $this->service->deletePlan($plan->id);

        $this->assertDatabaseMissing('membership_plans', ['id' => $plan->id]);
    }

    public function test_delete_plan_throws_when_active_subscriptions_exist(): void
    {
        $user = User::factory()->create();
        $plan = MembershipPlan::create([
            'name' => 'Protected Plan',
            'description' => null,
            'price' => '100.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        try {
            $this->service->deletePlan($plan->id);
            $this->fail('Expected PlanHasActiveSubscriptionsException was not thrown.');
        } catch (PlanHasActiveSubscriptionsException $e) {
            $this->assertEquals(1, $e->activeCount);
            $this->assertStringContainsString('1 active subscription', $e->getMessage());
        }

        $this->assertDatabaseHas('membership_plans', ['id' => $plan->id]);
    }

    public function test_delete_plan_throws_when_grace_period_subscriptions_exist(): void
    {
        $user = User::factory()->create();
        $plan = MembershipPlan::create([
            'name' => 'Grace Plan',
            'description' => null,
            'price' => '100.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'status' => 'grace_period',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'grace_period_ends_at' => now()->addDays(7),
        ]);

        $this->expectException(PlanHasActiveSubscriptionsException::class);
        $this->service->deletePlan($plan->id);
    }

    public function test_delete_plan_succeeds_when_only_cancelled_subscriptions_exist(): void
    {
        $user = User::factory()->create();
        $plan = MembershipPlan::create([
            'name' => 'Cancelled Plan',
            'description' => null,
            'price' => '100.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'status' => 'cancelled',
            'starts_at' => now()->subMonth(),
            'ends_at' => now(),
            'cancelled_at' => now()->subWeek(),
        ]);

        $this->service->deletePlan($plan->id);

        $this->assertDatabaseMissing('membership_plans', ['id' => $plan->id]);
    }

    public function test_delete_plan_throws_when_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->service->deletePlan(999);
    }

    public function test_delete_plan_detaches_courses(): void
    {
        $user = User::factory()->create();
        $course = Course::create([
            'instructor_id' => $user->id,
            'title' => 'Course',
            'description' => 'Desc',
            'category' => 'Cat',
            'status' => 'published',
        ]);

        $plan = MembershipPlan::create([
            'name' => 'Plan with Courses',
            'description' => null,
            'price' => '50.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);
        $plan->courses()->attach($course->id);

        $this->service->deletePlan($plan->id);

        $this->assertDatabaseMissing('membership_plans', ['id' => $plan->id]);
        $this->assertDatabaseMissing('course_membership_plan', [
            'membership_plan_id' => $plan->id,
        ]);
    }
}
