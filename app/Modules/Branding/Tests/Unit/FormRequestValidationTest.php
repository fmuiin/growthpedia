<?php

declare(strict_types=1);

use App\Modules\Branding\Requests\UpdateCreatorProfileRequest;
use App\Modules\Branding\Requests\UpdateLandingPageSectionRequest;
use App\Modules\Branding\Requests\UpdatePlatformBrandingRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Helper: run validation rules from a form request against given data.
 */
function validateRequest(string $requestClass, array $data): \Illuminate\Validation\Validator
{
    $request = new $requestClass();
    $request->merge($data);

    return validator($data, $request->rules());
}

// ─── UpdateCreatorProfileRequest ─────────────────────────────────────────────

test('creator profile: valid data passes validation', function () {
    $validator = validateRequest(UpdateCreatorProfileRequest::class, [
        'display_name' => 'Jane Creator',
        'bio' => 'I teach PHP and Laravel.',
        'expertise' => 'PHP Development',
    ]);

    expect($validator->passes())->toBeTrue();
});

test('creator profile: display_name exceeding 255 chars fails', function () {
    $validator = validateRequest(UpdateCreatorProfileRequest::class, [
        'display_name' => str_repeat('a', 256),
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('display_name'))->toBeTrue();
});

test('creator profile: bio exceeding 5000 chars fails', function () {
    $validator = validateRequest(UpdateCreatorProfileRequest::class, [
        'bio' => str_repeat('a', 5001),
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('bio'))->toBeTrue();
});

test('creator profile: avatar_url with invalid URL fails', function () {
    $validator = validateRequest(UpdateCreatorProfileRequest::class, [
        'avatar_url' => 'not-a-url',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('avatar_url'))->toBeTrue();
});

test('creator profile: valid social_links with URLs passes', function () {
    $validator = validateRequest(UpdateCreatorProfileRequest::class, [
        'social_links' => [
            'twitter' => 'https://twitter.com/janecreator',
            'linkedin' => 'https://linkedin.com/in/janecreator',
        ],
    ]);

    expect($validator->passes())->toBeTrue();
});

test('creator profile: social_links with invalid URL fails', function () {
    $validator = validateRequest(UpdateCreatorProfileRequest::class, [
        'social_links' => [
            'twitter' => 'not-a-valid-url',
        ],
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('social_links.twitter'))->toBeTrue();
});

test('creator profile: featured_course_ids with non-existent course ID fails', function () {
    $validator = validateRequest(UpdateCreatorProfileRequest::class, [
        'featured_course_ids' => [99999],
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('featured_course_ids.0'))->toBeTrue();
});

// ─── UpdateLandingPageSectionRequest ─────────────────────────────────────────

test('landing page section: valid section with type hero passes', function () {
    $validator = validateRequest(UpdateLandingPageSectionRequest::class, [
        'section_type' => 'hero',
        'title' => 'Welcome to GrowthPedia',
        'sort_order' => 1,
        'is_visible' => true,
    ]);

    expect($validator->passes())->toBeTrue();
});

test('landing page section: valid section with type about passes', function () {
    $validator = validateRequest(UpdateLandingPageSectionRequest::class, [
        'section_type' => 'about',
        'title' => 'About Us',
        'sort_order' => 2,
        'is_visible' => true,
    ]);

    expect($validator->passes())->toBeTrue();
});

test('landing page section: valid section with type featured_courses passes', function () {
    $validator = validateRequest(UpdateLandingPageSectionRequest::class, [
        'section_type' => 'featured_courses',
        'title' => 'Our Courses',
        'sort_order' => 3,
        'is_visible' => true,
    ]);

    expect($validator->passes())->toBeTrue();
});

test('landing page section: valid section with type testimonials passes', function () {
    $validator = validateRequest(UpdateLandingPageSectionRequest::class, [
        'section_type' => 'testimonials',
        'title' => 'What Students Say',
        'sort_order' => 4,
        'is_visible' => true,
    ]);

    expect($validator->passes())->toBeTrue();
});

test('landing page section: valid section with type cta passes', function () {
    $validator = validateRequest(UpdateLandingPageSectionRequest::class, [
        'section_type' => 'cta',
        'title' => 'Get Started',
        'cta_text' => 'Sign Up Now',
        'cta_url' => 'https://example.com/signup',
        'sort_order' => 5,
        'is_visible' => true,
    ]);

    expect($validator->passes())->toBeTrue();
});

test('landing page section: invalid section_type fails', function () {
    $validator = validateRequest(UpdateLandingPageSectionRequest::class, [
        'section_type' => 'invalid_type',
        'sort_order' => 1,
        'is_visible' => true,
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('section_type'))->toBeTrue();
});

test('landing page section: missing section_type fails', function () {
    $validator = validateRequest(UpdateLandingPageSectionRequest::class, [
        'sort_order' => 1,
        'is_visible' => true,
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('section_type'))->toBeTrue();
});

test('landing page section: missing sort_order fails', function () {
    $validator = validateRequest(UpdateLandingPageSectionRequest::class, [
        'section_type' => 'hero',
        'is_visible' => true,
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('sort_order'))->toBeTrue();
});

test('landing page section: missing is_visible fails', function () {
    $validator = validateRequest(UpdateLandingPageSectionRequest::class, [
        'section_type' => 'hero',
        'sort_order' => 1,
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('is_visible'))->toBeTrue();
});

test('landing page section: valid image_url passes, invalid image_url fails', function () {
    $validValidator = validateRequest(UpdateLandingPageSectionRequest::class, [
        'section_type' => 'hero',
        'image_url' => 'https://example.com/hero.jpg',
        'sort_order' => 1,
        'is_visible' => true,
    ]);

    expect($validValidator->passes())->toBeTrue();

    $invalidValidator = validateRequest(UpdateLandingPageSectionRequest::class, [
        'section_type' => 'hero',
        'image_url' => 'not-a-url',
        'sort_order' => 1,
        'is_visible' => true,
    ]);

    expect($invalidValidator->fails())->toBeTrue()
        ->and($invalidValidator->errors()->has('image_url'))->toBeTrue();
});

// ─── UpdatePlatformBrandingRequest ───────────────────────────────────────────

test('platform branding: valid hex color #3B82F6 passes for primary_color', function () {
    $validator = validateRequest(UpdatePlatformBrandingRequest::class, [
        'primary_color' => '#3B82F6',
    ]);

    expect($validator->passes())->toBeTrue();
});

test('platform branding: valid hex color #1E40AF passes for secondary_color', function () {
    $validator = validateRequest(UpdatePlatformBrandingRequest::class, [
        'secondary_color' => '#1E40AF',
    ]);

    expect($validator->passes())->toBeTrue();
});

test('platform branding: invalid color "red" fails for primary_color', function () {
    $validator = validateRequest(UpdatePlatformBrandingRequest::class, [
        'primary_color' => 'red',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('primary_color'))->toBeTrue();
});

test('platform branding: invalid color "#GGG" fails for primary_color', function () {
    $validator = validateRequest(UpdatePlatformBrandingRequest::class, [
        'primary_color' => '#GGG',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('primary_color'))->toBeTrue();
});

test('platform branding: invalid color "3B82F6" (missing #) fails for primary_color', function () {
    $validator = validateRequest(UpdatePlatformBrandingRequest::class, [
        'primary_color' => '3B82F6',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('primary_color'))->toBeTrue();
});

test('platform branding: invalid color "#3B82F" (5 chars) fails for primary_color', function () {
    $validator = validateRequest(UpdatePlatformBrandingRequest::class, [
        'primary_color' => '#3B82F',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('primary_color'))->toBeTrue();
});

test('platform branding: valid site_name passes', function () {
    $validator = validateRequest(UpdatePlatformBrandingRequest::class, [
        'site_name' => 'My Learning Platform',
    ]);

    expect($validator->passes())->toBeTrue();
});

test('platform branding: site_name exceeding 255 chars fails', function () {
    $validator = validateRequest(UpdatePlatformBrandingRequest::class, [
        'site_name' => str_repeat('a', 256),
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('site_name'))->toBeTrue();
});

test('platform branding: valid logo_url passes, invalid logo_url fails', function () {
    $validValidator = validateRequest(UpdatePlatformBrandingRequest::class, [
        'logo_url' => 'https://example.com/logo.png',
    ]);

    expect($validValidator->passes())->toBeTrue();

    $invalidValidator = validateRequest(UpdatePlatformBrandingRequest::class, [
        'logo_url' => 'not-a-url',
    ]);

    expect($invalidValidator->fails())->toBeTrue()
        ->and($invalidValidator->errors()->has('logo_url'))->toBeTrue();
});
