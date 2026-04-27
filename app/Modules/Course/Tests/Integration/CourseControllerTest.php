<?php

declare(strict_types=1);

namespace App\Modules\Course\Tests\Integration;

use App\Models\User;
use App\Modules\Course\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------
    // Requirement 2.5: Admin sees all courses
    // -------------------------------------------------------

    public function test_admin_sees_all_courses_regardless_of_creator(): void
    {
        $admin1 = User::factory()->create(['role' => 'admin']);
        $admin2 = User::factory()->create(['role' => 'admin']);

        Course::create([
            'created_by' => $admin1->id,
            'title' => 'Course by Admin 1',
            'description' => 'Description',
            'category' => 'Programming',
        ]);

        Course::create([
            'created_by' => $admin2->id,
            'title' => 'Course by Admin 2',
            'description' => 'Description',
            'category' => 'Design',
        ]);

        $response = $this->actingAs($admin1)->get(route('courses.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Course/CourseList')
            ->has('courses', 2)
        );
    }

    // -------------------------------------------------------
    // Requirement 2.3: Admin-only course creation
    // -------------------------------------------------------

    public function test_admin_can_create_course_with_title_description_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('courses.store'), [
            'title' => 'New Course',
            'description' => 'A course description',
            'category' => 'Programming',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('courses', [
            'created_by' => $admin->id,
            'title' => 'New Course',
            'description' => 'A course description',
            'category' => 'Programming',
            'status' => 'draft',
        ]);
    }

    public function test_created_course_has_draft_status_and_admin_as_creator(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('courses.store'), [
            'title' => 'Draft Course',
            'description' => 'Description',
            'category' => 'Design',
        ]);

        $course = Course::where('title', 'Draft Course')->first();

        $this->assertNotNull($course);
        $this->assertEquals('draft', $course->status);
        $this->assertEquals($admin->id, $course->created_by);
    }

    // -------------------------------------------------------
    // Requirement 2.4: Learner rejection
    // -------------------------------------------------------

    public function test_learner_cannot_create_course(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);

        $response = $this->actingAs($learner)->post(route('courses.store'), [
            'title' => 'Unauthorized Course',
            'description' => 'Should not be created',
            'category' => 'Hacking',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('courses', ['title' => 'Unauthorized Course']);
    }

    public function test_learner_cannot_access_course_index(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);

        $response = $this->actingAs($learner)->get(route('courses.index'));

        $response->assertStatus(403);
    }

    // -------------------------------------------------------
    // Requirement 2.10: Middleware verification
    // -------------------------------------------------------

    public function test_unauthenticated_user_is_redirected_on_course_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = Course::create([
            'created_by' => $admin->id,
            'title' => 'Test Course',
            'description' => 'Description',
            'category' => 'Programming',
        ]);

        // GET /courses (index)
        $this->get(route('courses.index'))->assertRedirect();

        // GET /courses/create
        $this->get(route('courses.create'))->assertRedirect();

        // POST /courses (store)
        $this->post(route('courses.store'), [
            'title' => 'Test',
            'description' => 'Test',
            'category' => 'Test',
        ])->assertRedirect();

        // GET /courses/{course}/edit
        $this->get(route('courses.edit', $course->id))->assertRedirect();

        // PUT /courses/{course} (update)
        $this->put(route('courses.update', $course->id), [
            'title' => 'Updated',
            'description' => 'Updated',
            'category' => 'Updated',
        ])->assertRedirect();

        // POST /courses/{course}/publish
        $this->post(route('courses.publish', $course->id))->assertRedirect();

        // POST /courses/{course}/unpublish
        $this->post(route('courses.unpublish', $course->id))->assertRedirect();

        // DELETE /courses/{course}
        $this->delete(route('courses.destroy', $course->id))->assertRedirect();
    }

    public function test_learner_gets_403_on_all_course_management_routes(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $admin = User::factory()->create(['role' => 'admin']);
        $course = Course::create([
            'created_by' => $admin->id,
            'title' => 'Test Course',
            'description' => 'Description',
            'category' => 'Programming',
        ]);

        $this->actingAs($learner)->get(route('courses.index'))->assertStatus(403);
        $this->actingAs($learner)->get(route('courses.create'))->assertStatus(403);
        $this->actingAs($learner)->post(route('courses.store'), [
            'title' => 'Test',
            'description' => 'Test',
            'category' => 'Test',
        ])->assertStatus(403);
        $this->actingAs($learner)->get(route('courses.edit', $course->id))->assertStatus(403);
        $this->actingAs($learner)->put(route('courses.update', $course->id), [
            'title' => 'Updated',
            'description' => 'Updated',
            'category' => 'Updated',
        ])->assertStatus(403);
        $this->actingAs($learner)->post(route('courses.publish', $course->id))->assertStatus(403);
        $this->actingAs($learner)->post(route('courses.unpublish', $course->id))->assertStatus(403);
        $this->actingAs($learner)->delete(route('courses.destroy', $course->id))->assertStatus(403);
    }

    // -------------------------------------------------------
    // Requirement 2.6: Admin can update any course
    // -------------------------------------------------------

    public function test_admin_can_update_course_created_by_different_admin(): void
    {
        $admin1 = User::factory()->create(['role' => 'admin']);
        $admin2 = User::factory()->create(['role' => 'admin']);

        $course = Course::create([
            'created_by' => $admin1->id,
            'title' => 'Original Title',
            'description' => 'Original Description',
            'category' => 'Programming',
        ]);

        $response = $this->actingAs($admin2)->put(route('courses.update', $course->id), [
            'title' => 'Updated by Admin 2',
            'description' => 'Updated Description',
            'category' => 'Design',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $course->refresh();
        $this->assertEquals('Updated by Admin 2', $course->title);
        $this->assertEquals('Updated Description', $course->description);
        $this->assertEquals('Design', $course->category);
    }

    // -------------------------------------------------------
    // Requirements 2.8, 2.9: Admin can delete draft but not published courses
    // -------------------------------------------------------

    public function test_admin_can_delete_draft_course(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $course = Course::create([
            'created_by' => $admin->id,
            'title' => 'Draft Course',
            'description' => 'Description',
            'category' => 'Programming',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin)->delete(route('courses.destroy', $course->id));

        $response->assertRedirect(route('courses.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_admin_cannot_delete_published_course(): void
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

        $response = $this->actingAs($admin)->delete(route('courses.destroy', $course->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }
}
