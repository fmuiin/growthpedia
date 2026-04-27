<?php

declare(strict_types=1);

namespace App\Modules\Course\Tests\Unit;

use App\Models\User;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Policies\CoursePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoursePolicyTest extends TestCase
{
    use RefreshDatabase;

    private CoursePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CoursePolicy();
    }

    // --- viewAny ---

    public function test_any_user_can_view_any_courses(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $learner = User::factory()->create(['role' => 'learner']);

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->viewAny($learner));
    }

    // --- view ---

    public function test_any_user_can_view_a_course(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $learner = User::factory()->create(['role' => 'learner']);
        $course = Course::create([
            'created_by' => $admin->id,
            'title' => 'Test Course',
            'description' => 'Description',
            'category' => 'Programming',
        ]);

        $this->assertTrue($this->policy->view($admin, $course));
        $this->assertTrue($this->policy->view($learner, $course));
    }

    // --- create ---

    public function test_admin_can_create_courses(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($this->policy->create($admin));
    }

    public function test_learner_cannot_create_courses(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);

        $this->assertFalse($this->policy->create($learner));
    }

    // --- update ---

    public function test_admin_can_update_any_course(): void
    {
        $admin1 = User::factory()->create(['role' => 'admin']);
        $admin2 = User::factory()->create(['role' => 'admin']);
        $course = Course::create([
            'created_by' => $admin1->id,
            'title' => 'Test Course',
            'description' => 'Description',
            'category' => 'Programming',
        ]);

        // Admin who created the course can update it
        $this->assertTrue($this->policy->update($admin1, $course));
        // A different admin can also update it (no ownership check)
        $this->assertTrue($this->policy->update($admin2, $course));
    }

    public function test_learner_cannot_update_courses(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $learner = User::factory()->create(['role' => 'learner']);
        $course = Course::create([
            'created_by' => $admin->id,
            'title' => 'Test Course',
            'description' => 'Description',
            'category' => 'Programming',
        ]);

        $this->assertFalse($this->policy->update($learner, $course));
    }

    // --- publish ---

    public function test_admin_can_publish_courses(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = Course::create([
            'created_by' => $admin->id,
            'title' => 'Test Course',
            'description' => 'Description',
            'category' => 'Programming',
        ]);

        $this->assertTrue($this->policy->publish($admin, $course));
    }

    public function test_learner_cannot_publish_courses(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $learner = User::factory()->create(['role' => 'learner']);
        $course = Course::create([
            'created_by' => $admin->id,
            'title' => 'Test Course',
            'description' => 'Description',
            'category' => 'Programming',
        ]);

        $this->assertFalse($this->policy->publish($learner, $course));
    }

    // --- delete ---

    public function test_admin_can_delete_draft_courses(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = Course::create([
            'created_by' => $admin->id,
            'title' => 'Draft Course',
            'description' => 'Description',
            'category' => 'Programming',
            'status' => 'draft',
        ]);

        $this->assertTrue($this->policy->delete($admin, $course));
    }

    public function test_admin_cannot_delete_published_courses(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = Course::create([
            'created_by' => $admin->id,
            'title' => 'Published Course',
            'description' => 'Description',
            'category' => 'Programming',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->assertFalse($this->policy->delete($admin, $course));
    }

    public function test_learner_cannot_delete_any_course(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $learner = User::factory()->create(['role' => 'learner']);

        $draftCourse = Course::create([
            'created_by' => $admin->id,
            'title' => 'Draft Course',
            'description' => 'Description',
            'category' => 'Programming',
            'status' => 'draft',
        ]);

        $publishedCourse = Course::create([
            'created_by' => $admin->id,
            'title' => 'Published Course',
            'description' => 'Description',
            'category' => 'Programming',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->assertFalse($this->policy->delete($learner, $draftCourse));
        $this->assertFalse($this->policy->delete($learner, $publishedCourse));
    }
}
