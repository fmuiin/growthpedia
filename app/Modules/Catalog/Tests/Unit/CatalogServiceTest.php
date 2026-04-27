<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Tests\Unit;

use App\Models\User;
use App\Modules\Branding\Contracts\BrandingServiceInterface;
use App\Modules\Branding\DTOs\CreatorProfileDTO;
use App\Modules\Catalog\DTOs\CatalogCourseDetailDTO;
use App\Modules\Catalog\DTOs\CatalogCourseDTO;
use App\Modules\Catalog\DTOs\PaginatedCoursesDTO;
use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Models\CourseModule;
use App\Modules\Course\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class CatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    private CatalogService $service;

    private BrandingServiceInterface $brandingMock;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        // SQLite does not support the PostgreSQL 'ilike' operator used by CatalogService::search().
        // Replace the default SQLite query grammar with one that rewrites 'ilike' to 'like'
        // (SQLite LIKE is already case-insensitive for ASCII).
        $connection = \Illuminate\Support\Facades\DB::connection();
        if ($connection->getDriverName() === 'sqlite') {
            $grammar = new class($connection) extends \Illuminate\Database\Query\Grammars\SQLiteGrammar
            {
                protected function whereBasic(\Illuminate\Database\Query\Builder $query, $where): string
                {
                    if (isset($where['operator']) && strtolower($where['operator']) === 'ilike') {
                        $where['operator'] = 'like';
                    }

                    return parent::whereBasic($query, $where);
                }
            };
            $connection->setQueryGrammar($grammar);
        }

        $this->brandingMock = Mockery::mock(BrandingServiceInterface::class);
        $this->brandingMock->shouldReceive('getCreatorName')
            ->andReturn('Test Creator Name')
            ->byDefault();

        $this->service = new CatalogService($this->brandingMock);
    }

    // --- browse() returns creatorName from BrandingService (Req 6.1) ---

    public function test_browse_returns_creator_name_from_branding_service(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Course::create([
            'created_by' => $admin->id,
            'title' => 'Course One',
            'description' => 'Description one',
            'category' => 'PHP',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Course::create([
            'created_by' => $admin->id,
            'title' => 'Course Two',
            'description' => 'Description two',
            'category' => 'JavaScript',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $result = $this->service->browse();

        $this->assertInstanceOf(PaginatedCoursesDTO::class, $result);
        $this->assertCount(2, $result->data);

        foreach ($result->data as $courseDto) {
            $this->assertInstanceOf(CatalogCourseDTO::class, $courseDto);
            $this->assertEquals('Test Creator Name', $courseDto->creatorName);
        }
    }

    public function test_browse_returns_same_creator_name_for_all_courses(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);

        $this->brandingMock->shouldReceive('getCreatorName')
            ->andReturn('Single Creator');

        Course::create([
            'created_by' => $admin->id,
            'title' => 'Admin Course',
            'description' => 'By admin',
            'category' => 'PHP',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Course::create([
            'created_by' => $otherAdmin->id,
            'title' => 'Other Admin Course',
            'description' => 'By other admin',
            'category' => 'JS',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $result = $this->service->browse();

        $creatorNames = array_map(fn (CatalogCourseDTO $dto) => $dto->creatorName, $result->data);
        $this->assertCount(2, $creatorNames);
        $this->assertEquals(['Single Creator', 'Single Creator'], $creatorNames);
    }

    // --- getCourseDetail() returns creatorName and creatorBio from BrandingService (Req 6.2) ---

    public function test_get_course_detail_returns_creator_name_and_bio_from_branding_service(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->brandingMock->shouldReceive('getCreatorName')
            ->andReturn('Branding Creator');

        $this->brandingMock->shouldReceive('getCreatorProfile')
            ->andReturn(new CreatorProfileDTO(
                id: 1,
                userId: $admin->id,
                displayName: 'Branding Creator',
                bio: 'Expert in web development',
                avatarUrl: null,
                expertise: null,
                socialLinks: null,
                featuredCourseIds: null,
            ));

        $course = Course::create([
            'created_by' => $admin->id,
            'title' => 'Detail Course',
            'description' => 'A detailed course',
            'category' => 'PHP',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module 1',
            'sort_order' => 1,
        ]);

        Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 1',
            'content_type' => 'text',
            'content_body' => 'Content',
            'sort_order' => 1,
        ]);

        $result = $this->service->getCourseDetail($course->id);

        $this->assertInstanceOf(CatalogCourseDetailDTO::class, $result);
        $this->assertEquals('Branding Creator', $result->creatorName);
        $this->assertEquals('Expert in web development', $result->creatorBio);
    }

    // --- search() returns creatorName from BrandingService (Req 6.4) ---

    public function test_search_returns_creator_name_from_branding_service(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->brandingMock->shouldReceive('getCreatorName')
            ->andReturn('Search Creator');

        Course::create([
            'created_by' => $admin->id,
            'title' => 'Laravel Fundamentals',
            'description' => 'Learn Laravel basics',
            'category' => 'PHP',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Course::create([
            'created_by' => $admin->id,
            'title' => 'React Basics',
            'description' => 'Learn React',
            'category' => 'JavaScript',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $result = $this->service->search('Laravel');

        $this->assertInstanceOf(PaginatedCoursesDTO::class, $result);
        $this->assertCount(1, $result->data);
        $this->assertEquals('Search Creator', $result->data[0]->creatorName);
    }

    public function test_search_returns_same_creator_name_for_all_matching_courses(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->brandingMock->shouldReceive('getCreatorName')
            ->andReturn('Platform Creator');

        Course::create([
            'created_by' => $admin->id,
            'title' => 'PHP Basics',
            'description' => 'Learn PHP',
            'category' => 'PHP',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Course::create([
            'created_by' => $admin->id,
            'title' => 'PHP Advanced',
            'description' => 'Advanced PHP',
            'category' => 'PHP',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $result = $this->service->search('PHP');

        $this->assertCount(2, $result->data);
        foreach ($result->data as $courseDto) {
            $this->assertEquals('Platform Creator', $courseDto->creatorName);
        }
    }

    // --- No instructor eager-loading (Req 6.3) ---
    // This is implicitly verified by the tests above: the CatalogService constructor
    // requires BrandingServiceInterface (not an instructor relationship), and the DTOs
    // use creatorName/creatorBio fields sourced from the branding mock. If the service
    // tried to access an instructor relationship, the tests would fail since no such
    // relationship is loaded or available.

    public function test_browse_does_not_require_instructor_relationship(): void
    {
        // Create a course with a created_by user — no instructor relationship exists
        $admin = User::factory()->create(['role' => 'admin']);

        Course::create([
            'created_by' => $admin->id,
            'title' => 'No Instructor Course',
            'description' => 'This course has no instructor relationship',
            'category' => 'General',
            'status' => 'published',
            'published_at' => now(),
        ]);

        // browse() succeeds without any instructor eager-loading
        $result = $this->service->browse();

        $this->assertCount(1, $result->data);
        $this->assertEquals('Test Creator Name', $result->data[0]->creatorName);
    }
}
