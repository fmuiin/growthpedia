<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Branding\Models\CreatorProfile;
use App\Modules\Branding\Models\LandingPageSection;
use App\Modules\Branding\Models\PlatformBranding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

// --- Creator Profile CRUD (admin) ---

test('admin can view creator profile edit page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/admin/branding/profile');

    $response->assertStatus(200);
});

test('admin can update creator profile with valid data', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->put('/admin/branding/profile', [
        'display_name' => 'Updated Creator',
        'bio' => 'My updated bio',
        'expertise' => 'Laravel Development',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

test('admin can update creator profile with social links', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $socialLinks = [
        'twitter' => 'https://twitter.com/creator',
        'linkedin' => 'https://linkedin.com/in/creator',
        'youtube' => 'https://youtube.com/@creator',
        'website' => 'https://example.com',
    ];

    $response = $this->actingAs($admin)->put('/admin/branding/profile', [
        'display_name' => 'Social Creator',
        'social_links' => $socialLinks,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $profile = CreatorProfile::query()->first();
    expect($profile->social_links)->toBe($socialLinks);
});

test('updated creator profile data is reflected in database', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->put('/admin/branding/profile', [
        'display_name' => 'Persisted Name',
        'bio' => 'Persisted bio text',
        'expertise' => 'PHP & Laravel',
    ]);

    $profile = CreatorProfile::query()->first();
    expect($profile->display_name)->toBe('Persisted Name')
        ->and($profile->bio)->toBe('Persisted bio text')
        ->and($profile->expertise)->toBe('PHP & Laravel');
});

// --- Landing Sections CRUD (admin) ---

test('admin can view landing page editor', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/admin/branding/landing-sections');

    $response->assertStatus(200);
});

test('admin can create a landing section with valid data', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->post('/admin/branding/landing-sections', [
        'section_type' => 'hero',
        'title' => 'Welcome to GrowthPedia',
        'subtitle' => 'Learn and grow',
        'sort_order' => 1,
        'is_visible' => true,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(LandingPageSection::count())->toBe(1);
    $section = LandingPageSection::query()->first();
    expect($section->section_type)->toBe('hero')
        ->and($section->title)->toBe('Welcome to GrowthPedia');
});

test('admin can update an existing landing section', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $section = LandingPageSection::create([
        'section_type' => 'hero',
        'title' => 'Old Title',
        'sort_order' => 1,
        'is_visible' => true,
    ]);

    $response = $this->actingAs($admin)->put("/admin/branding/landing-sections/{$section->id}", [
        'section_type' => 'hero',
        'title' => 'New Title',
        'sort_order' => 1,
        'is_visible' => true,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $section->refresh();
    expect($section->title)->toBe('New Title');
});

test('admin can delete a landing section', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $section = LandingPageSection::create([
        'section_type' => 'about',
        'title' => 'About Us',
        'sort_order' => 1,
        'is_visible' => true,
    ]);

    $response = $this->actingAs($admin)->delete("/admin/branding/landing-sections/{$section->id}");

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(LandingPageSection::count())->toBe(0);
});

test('creating a section with invalid section_type returns validation error', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->post('/admin/branding/landing-sections', [
        'section_type' => 'invalid_type',
        'title' => 'Bad Section',
        'sort_order' => 1,
        'is_visible' => true,
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors('section_type');
});

// --- Platform Branding (admin) ---

test('admin can view platform branding page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/admin/branding/platform');

    $response->assertStatus(200);
});

test('admin can update platform branding with valid data', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->put('/admin/branding/platform', [
        'site_name' => 'My Learning Hub',
        'tagline' => 'Learn something new every day',
        'primary_color' => '#FF5733',
        'secondary_color' => '#C70039',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $branding = PlatformBranding::query()->first();
    expect($branding->site_name)->toBe('My Learning Hub')
        ->and($branding->primary_color)->toBe('#FF5733');
});

test('updating platform branding with invalid hex color returns validation error', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->put('/admin/branding/platform', [
        'primary_color' => 'not-a-hex-color',
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors('primary_color');
});

// --- Non-admin gets 403 ---

test('learner gets 403 on GET /admin/branding/profile', function () {
    $learner = User::factory()->create(['role' => 'learner']);

    $response = $this->actingAs($learner)->get('/admin/branding/profile');

    $response->assertStatus(403);
});

test('learner gets 403 on PUT /admin/branding/profile', function () {
    $learner = User::factory()->create(['role' => 'learner']);

    $response = $this->actingAs($learner)->put('/admin/branding/profile', [
        'display_name' => 'Hacker',
    ]);

    $response->assertStatus(403);
});

test('learner gets 403 on GET /admin/branding/landing-sections', function () {
    $learner = User::factory()->create(['role' => 'learner']);

    $response = $this->actingAs($learner)->get('/admin/branding/landing-sections');

    $response->assertStatus(403);
});

test('learner gets 403 on POST /admin/branding/landing-sections', function () {
    $learner = User::factory()->create(['role' => 'learner']);

    $response = $this->actingAs($learner)->post('/admin/branding/landing-sections', [
        'section_type' => 'hero',
        'title' => 'Unauthorized',
        'sort_order' => 1,
        'is_visible' => true,
    ]);

    $response->assertStatus(403);
});

test('learner gets 403 on PUT /admin/branding/platform', function () {
    $learner = User::factory()->create(['role' => 'learner']);

    $response = $this->actingAs($learner)->put('/admin/branding/platform', [
        'site_name' => 'Hacked Platform',
    ]);

    $response->assertStatus(403);
});

test('unauthenticated user gets redirected on admin branding routes', function () {
    $response = $this->get('/admin/branding/profile');
    $response->assertRedirect();

    $response = $this->put('/admin/branding/profile', ['display_name' => 'Test']);
    $response->assertRedirect();

    $response = $this->get('/admin/branding/landing-sections');
    $response->assertRedirect();

    $response = $this->get('/admin/branding/platform');
    $response->assertRedirect();
});

// --- Public /creator page ---

test('public creator page renders correctly when creator profile exists', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    CreatorProfile::create([
        'user_id' => $admin->id,
        'display_name' => 'Public Creator',
        'bio' => 'A public bio',
        'expertise' => 'Teaching',
    ]);

    $response = $this->get('/creator');

    $response->assertStatus(200);
});

test('public creator page is accessible without authentication', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    CreatorProfile::create([
        'user_id' => $admin->id,
        'display_name' => 'Accessible Creator',
    ]);

    $response = $this->get('/creator');

    $response->assertStatus(200);
});
