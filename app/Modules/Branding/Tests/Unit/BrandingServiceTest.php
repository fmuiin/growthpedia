<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Branding\DTOs\CreatorProfileDTO;
use App\Modules\Branding\DTOs\LandingPageDTO;
use App\Modules\Branding\DTOs\PlatformBrandingDTO;
use App\Modules\Branding\DTOs\UpdateCreatorProfileDTO;
use App\Modules\Branding\DTOs\UpdateLandingPageDTO;
use App\Modules\Branding\DTOs\UpdatePlatformBrandingDTO;
use App\Modules\Branding\Models\CreatorProfile;
use App\Modules\Branding\Models\LandingPageSection;
use App\Modules\Branding\Models\PlatformBranding;
use App\Modules\Branding\Services\BrandingService;
use App\Modules\Course\Models\Course;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new BrandingService();
    Cache::flush();
});

// --- getCreatorProfile ---

test('getCreatorProfile auto-initializes from first admin user when no profile exists', function () {
    $admin = User::factory()->create(['role' => 'admin', 'name' => 'Jane Creator']);

    $result = $this->service->getCreatorProfile();

    expect($result)->toBeInstanceOf(CreatorProfileDTO::class)
        ->and($result->userId)->toBe($admin->id)
        ->and($result->displayName)->toBe('Jane Creator')
        ->and($result->bio)->toBeNull()
        ->and($result->avatarUrl)->toBeNull()
        ->and($result->expertise)->toBeNull()
        ->and($result->socialLinks)->toBeNull()
        ->and($result->featuredCourseIds)->toBeNull();

    expect(CreatorProfile::count())->toBe(1);
});

test('getCreatorProfile returns existing profile without creating a new one', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $profile = CreatorProfile::create([
        'user_id' => $admin->id,
        'display_name' => 'Existing Creator',
        'bio' => 'My bio',
    ]);

    $result = $this->service->getCreatorProfile();

    expect($result->displayName)->toBe('Existing Creator')
        ->and($result->bio)->toBe('My bio')
        ->and($result->id)->toBe($profile->id);

    expect(CreatorProfile::count())->toBe(1);
});

test('getCreatorProfile throws EntityNotFoundException when no admin user exists', function () {
    User::factory()->create(['role' => 'learner']);

    $this->service->getCreatorProfile();
})->throws(EntityNotFoundException::class, 'No admin user found. Please create an admin account first.');

test('getCreatorProfile caches the result', function () {
    User::factory()->create(['role' => 'admin', 'name' => 'Admin']);

    $this->service->getCreatorProfile();

    expect(Cache::has('branding:profile'))->toBeTrue();
});

// --- updateCreatorProfile ---

test('updateCreatorProfile updates profile fields', function () {
    $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin']);

    $dto = new UpdateCreatorProfileDTO(
        displayName: 'Updated Name',
        bio: 'Updated bio',
        expertise: 'PHP Development',
    );

    $result = $this->service->updateCreatorProfile($dto);

    expect($result->displayName)->toBe('Updated Name')
        ->and($result->bio)->toBe('Updated bio')
        ->and($result->expertise)->toBe('PHP Development');
});

test('updateCreatorProfile validates featured_course_ids are published', function () {
    $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin']);

    $course = Course::create([
        'instructor_id' => $admin->id,
        'title' => 'Draft Course',
        'description' => 'A draft course',
        'category' => 'PHP',
        'status' => 'draft',
    ]);

    $dto = new UpdateCreatorProfileDTO(
        featuredCourseIds: [$course->id],
    );

    $this->service->updateCreatorProfile($dto);
})->throws(ValidationException::class);

test('updateCreatorProfile accepts published course ids', function () {
    $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin']);

    $course = Course::create([
        'instructor_id' => $admin->id,
        'title' => 'Published Course',
        'description' => 'A published course',
        'category' => 'PHP',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $dto = new UpdateCreatorProfileDTO(
        featuredCourseIds: [$course->id],
    );

    $result = $this->service->updateCreatorProfile($dto);

    expect($result->featuredCourseIds)->toBe([$course->id]);
});

test('updateCreatorProfile invalidates profile and landing cache', function () {
    $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin']);

    // Warm the caches
    $this->service->getCreatorProfile();
    Cache::put('branding:landing', 'cached-landing', 300);

    $dto = new UpdateCreatorProfileDTO(displayName: 'New Name');
    $this->service->updateCreatorProfile($dto);

    expect(Cache::has('branding:profile'))->toBeFalse()
        ->and(Cache::has('branding:landing'))->toBeFalse();
});

// --- getLandingPageContent ---

test('getLandingPageContent returns empty sections with default branding when no data exists', function () {
    $result = $this->service->getLandingPageContent();

    expect($result)->toBeInstanceOf(LandingPageDTO::class)
        ->and($result->sections)->toBeEmpty()
        ->and($result->branding->siteName)->toBe('GrowthPedia')
        ->and($result->branding->primaryColor)->toBe('#3B82F6')
        ->and($result->branding->secondaryColor)->toBe('#1E40AF')
        ->and($result->creatorProfile)->toBeNull();
});

test('getLandingPageContent returns only visible sections sorted by sort_order', function () {
    LandingPageSection::create([
        'section_type' => 'hero',
        'title' => 'Hero',
        'sort_order' => 2,
        'is_visible' => true,
    ]);
    LandingPageSection::create([
        'section_type' => 'about',
        'title' => 'About',
        'sort_order' => 1,
        'is_visible' => true,
    ]);
    LandingPageSection::create([
        'section_type' => 'cta',
        'title' => 'Hidden CTA',
        'sort_order' => 3,
        'is_visible' => false,
    ]);

    $result = $this->service->getLandingPageContent();

    expect($result->sections)->toHaveCount(2)
        ->and($result->sections[0]->title)->toBe('About')
        ->and($result->sections[0]->sortOrder)->toBe(1)
        ->and($result->sections[1]->title)->toBe('Hero')
        ->and($result->sections[1]->sortOrder)->toBe(2);
});

test('getLandingPageContent uses featured courses from creator profile when set', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $publishedCourse = Course::create([
        'instructor_id' => $admin->id,
        'title' => 'Featured Course',
        'description' => 'Featured',
        'category' => 'PHP',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $otherCourse = Course::create([
        'instructor_id' => $admin->id,
        'title' => 'Other Course',
        'description' => 'Other',
        'category' => 'JS',
        'status' => 'published',
        'published_at' => now(),
    ]);

    CreatorProfile::create([
        'user_id' => $admin->id,
        'display_name' => 'Creator',
        'featured_course_ids' => [$publishedCourse->id],
    ]);

    $result = $this->service->getLandingPageContent();

    expect($result->featuredCourses)->toHaveCount(1)
        ->and($result->featuredCourses[0]['title'])->toBe('Featured Course');
});

test('getLandingPageContent falls back to latest 6 published courses when no featured courses set', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    for ($i = 0; $i < 8; $i++) {
        Course::create([
            'instructor_id' => $admin->id,
            'title' => "Course {$i}",
            'description' => "Description {$i}",
            'category' => 'PHP',
            'status' => 'published',
            'published_at' => now()->subDays($i),
        ]);
    }

    $result = $this->service->getLandingPageContent();

    expect($result->featuredCourses)->toHaveCount(6);
});

test('getLandingPageContent caches the result', function () {
    $this->service->getLandingPageContent();

    expect(Cache::has('branding:landing'))->toBeTrue();
});

// --- updateLandingPageContent ---

test('updateLandingPageContent creates new sections', function () {
    $dto = new UpdateLandingPageDTO(
        sections: [
            [
                'section_type' => 'hero',
                'title' => 'Welcome',
                'subtitle' => 'Learn with us',
                'sort_order' => 1,
                'is_visible' => true,
            ],
        ],
    );

    $result = $this->service->updateLandingPageContent($dto);

    expect($result->sections)->toHaveCount(1)
        ->and($result->sections[0]->title)->toBe('Welcome')
        ->and(LandingPageSection::count())->toBe(1);
});

test('updateLandingPageContent updates existing sections by id', function () {
    $section = LandingPageSection::create([
        'section_type' => 'hero',
        'title' => 'Old Title',
        'sort_order' => 1,
        'is_visible' => true,
    ]);

    $dto = new UpdateLandingPageDTO(
        sections: [
            [
                'id' => $section->id,
                'title' => 'New Title',
            ],
        ],
    );

    $this->service->updateLandingPageContent($dto);

    $section->refresh();
    expect($section->title)->toBe('New Title');
});

test('updateLandingPageContent invalidates landing cache', function () {
    // Warm the cache with stale data
    Cache::put('branding:landing', 'stale-cached-data', 300);

    $dto = new UpdateLandingPageDTO(sections: [
        [
            'section_type' => 'hero',
            'title' => 'Fresh Section',
            'sort_order' => 1,
            'is_visible' => true,
        ],
    ]);
    $result = $this->service->updateLandingPageContent($dto);

    // The stale cache was invalidated and fresh data was returned
    expect($result)->toBeInstanceOf(LandingPageDTO::class)
        ->and($result->sections)->toHaveCount(1)
        ->and($result->sections[0]->title)->toBe('Fresh Section');
});

// --- getPlatformBranding ---

test('getPlatformBranding returns defaults when no record exists', function () {
    $result = $this->service->getPlatformBranding();

    expect($result)->toBeInstanceOf(PlatformBrandingDTO::class)
        ->and($result->id)->toBe(0)
        ->and($result->siteName)->toBe('GrowthPedia')
        ->and($result->primaryColor)->toBe('#3B82F6')
        ->and($result->secondaryColor)->toBe('#1E40AF')
        ->and($result->tagline)->toBeNull()
        ->and($result->logoUrl)->toBeNull();
});

test('getPlatformBranding returns existing record', function () {
    $branding = PlatformBranding::create([
        'site_name' => 'My Platform',
        'primary_color' => '#FF0000',
        'secondary_color' => '#00FF00',
        'tagline' => 'Learn everything',
    ]);

    $result = $this->service->getPlatformBranding();

    expect($result->id)->toBe($branding->id)
        ->and($result->siteName)->toBe('My Platform')
        ->and($result->primaryColor)->toBe('#FF0000')
        ->and($result->secondaryColor)->toBe('#00FF00')
        ->and($result->tagline)->toBe('Learn everything');
});

test('getPlatformBranding caches the result', function () {
    $this->service->getPlatformBranding();

    expect(Cache::has('branding:platform'))->toBeTrue();
});

// --- updatePlatformBranding ---

test('updatePlatformBranding creates a new record when none exists', function () {
    $dto = new UpdatePlatformBrandingDTO(
        siteName: 'New Platform',
        primaryColor: '#123456',
    );

    $result = $this->service->updatePlatformBranding($dto);

    expect($result->siteName)->toBe('New Platform')
        ->and($result->primaryColor)->toBe('#123456')
        ->and(PlatformBranding::count())->toBe(1);
});

test('updatePlatformBranding updates existing record', function () {
    PlatformBranding::create([
        'site_name' => 'Old Name',
        'primary_color' => '#000000',
        'secondary_color' => '#FFFFFF',
    ]);

    $dto = new UpdatePlatformBrandingDTO(
        siteName: 'Updated Name',
    );

    $result = $this->service->updatePlatformBranding($dto);

    expect($result->siteName)->toBe('Updated Name')
        ->and(PlatformBranding::count())->toBe(1);
});

test('updatePlatformBranding invalidates platform and landing cache', function () {
    Cache::put('branding:platform', 'cached-platform', 300);
    Cache::put('branding:landing', 'cached-landing', 300);

    $dto = new UpdatePlatformBrandingDTO(siteName: 'New');
    $this->service->updatePlatformBranding($dto);

    expect(Cache::has('branding:platform'))->toBeFalse()
        ->and(Cache::has('branding:landing'))->toBeFalse();
});

// --- getCreatorName ---

test('getCreatorName returns display_name from creator profile', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    CreatorProfile::create([
        'user_id' => $admin->id,
        'display_name' => 'The Creator',
    ]);

    $result = $this->service->getCreatorName();

    expect($result)->toBe('The Creator');
});

test('getCreatorName auto-initializes profile and returns admin name', function () {
    User::factory()->create(['role' => 'admin', 'name' => 'Admin User']);

    $result = $this->service->getCreatorName();

    expect($result)->toBe('Admin User');
});
