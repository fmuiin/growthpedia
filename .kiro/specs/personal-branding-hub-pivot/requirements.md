# Requirements: Personal Branding Hub Pivot

## Requirement 1: Instructor Role Removal and Role Simplification

### Description
Remove the 'instructor' role from the platform entirely. The user role system is simplified from three roles ('learner', 'instructor', 'admin') to two roles ('learner', 'admin'). All existing instructor users are promoted to admin. The role enum constraint in the database is updated to only allow the two remaining roles.

### Acceptance Criteria

- 1.1 Given users with role 'instructor' exist in the database, when the migration runs, then all instructor users are updated to role 'admin' and zero users with role 'instructor' remain.
- 1.2 Given the migration has completed, when any code attempts to assign the 'instructor' role via `UserService::assignRole()`, then a `ValidationException` is thrown with a message indicating the role is invalid.
- 1.3 Given the migration has completed, when the `AssignRoleRequest` validates a role value of 'instructor', then validation fails with an error indicating the role must be one of 'learner' or 'admin'.
- 1.4 Given the migration has completed, when a new user registers, then their default role is 'learner' (unchanged behavior).
- 1.5 Given the migration has completed, when the admin user management page loads, then the role assignment dropdown only shows 'learner' and 'admin' options.

## Requirement 2: Course Ownership Simplification

### Description
Replace the multi-instructor course ownership model with a single-creator model. The `instructor_id` column on the `courses` table is renamed to `created_by`. All courses are owned by admin users. The `CreateCourseDTO` no longer requires an `instructorId` field — the `created_by` value is auto-set from the authenticated admin user. Course policies are simplified to check only for admin role, removing per-instructor ownership checks.

### Acceptance Criteria

- 2.1 Given the migration has completed, when querying the `courses` table schema, then the column `instructor_id` no longer exists and a column `created_by` exists with a foreign key constraint to the `users` table.
- 2.2 Given existing courses with `instructor_id` values, when the migration runs, then all data is preserved — every course's `created_by` value equals its former `instructor_id` value.
- 2.3 Given an authenticated admin user, when they create a new course via `POST /courses` with title, description, and category, then the course is created with `created_by` set to the admin user's ID and `status` set to 'draft'.
- 2.4 Given an authenticated user with role 'learner', when they attempt to create a course via `POST /courses`, then the request is rejected with a 403 Forbidden response.
- 2.5 Given an authenticated admin user, when they access `GET /courses`, then all courses in the system are listed (not filtered by the admin's user ID).
- 2.6 Given an authenticated admin user and any course, when `CoursePolicy::update()` is evaluated, then it returns true regardless of which admin created the course.
- 2.7 Given an authenticated user with role 'learner' and any course, when `CoursePolicy::update()` is evaluated, then it returns false.
- 2.8 Given a course with status 'draft' and an authenticated admin user, when `CoursePolicy::delete()` is evaluated, then it returns true.
- 2.9 Given a course with status 'published' and an authenticated admin user, when `CoursePolicy::delete()` is evaluated, then it returns false (only draft courses can be deleted).
- 2.10 Given the Course module routes, when the middleware is inspected, then the route group uses `role:admin` middleware instead of `role:instructor,admin`.

## Requirement 3: Creator Profile Management

### Description
A new Creator Profile feature allows the admin (creator) to manage their personal brand identity. The profile includes display name, bio, avatar URL, expertise area, social media links, and featured course selections. At most one creator profile exists in the system (single-creator platform). The profile is auto-initialized from the admin user's data when first accessed.

### Acceptance Criteria

- 3.1 Given an admin user and no existing creator profile, when `BrandingService::getCreatorProfile()` is called, then a new creator profile is created with `display_name` set to the admin user's name and all other fields set to null/default, and the profile DTO is returned.
- 3.2 Given an existing creator profile, when the admin updates it via `PUT /admin/branding/profile` with valid data (display_name, bio, avatar_url, expertise, social_links), then the profile is updated and the updated DTO is returned.
- 3.3 Given an existing creator profile, when the admin sets `featured_course_ids` to an array containing only published course IDs, then the update succeeds and the featured courses are stored.
- 3.4 Given an existing creator profile, when the admin sets `featured_course_ids` to an array containing an unpublished or draft course ID, then a `ValidationException` is thrown with a message indicating featured courses must be published.
- 3.5 Given the `creator_profiles` table, when a second profile is attempted for a different user_id, then the database unique constraint on `user_id` prevents the duplicate.
- 3.6 Given a visitor accessing `GET /creator`, when the creator profile exists, then the page renders with the creator's display name, bio, avatar, expertise, social links, and featured courses.
- 3.7 Given a non-admin user, when they attempt to access `PUT /admin/branding/profile`, then the request is rejected with a 403 Forbidden response.

## Requirement 4: Landing Page Content Management

### Description
The admin can manage dynamic landing page content through configurable sections. Each section has a type (hero, about, featured_courses, testimonials, cta), optional title/subtitle/content/image, CTA button configuration, sort order, and visibility toggle. The landing page assembles all visible sections in sort order along with platform branding and featured courses.

### Acceptance Criteria

- 4.1 Given an admin user, when they create a landing page section via the branding admin interface with section_type 'hero', title, subtitle, and image_url, then the section is stored in the `landing_page_sections` table with `is_visible` defaulting to true.
- 4.2 Given multiple landing page sections with different sort_order values, when `BrandingService::getLandingPageContent()` is called, then the returned sections are ordered by `sort_order` ascending.
- 4.3 Given landing page sections where some have `is_visible = false`, when `BrandingService::getLandingPageContent()` is called, then only sections with `is_visible = true` are included in the result.
- 4.4 Given a landing page section, when the admin updates its `is_visible` to false, then the section no longer appears in the landing page content but is not deleted from the database.
- 4.5 Given a section_type value not in the allowed list ('hero', 'about', 'featured_courses', 'testimonials', 'cta'), when the admin attempts to create a section, then validation fails.
- 4.6 Given no landing page sections exist, when `BrandingService::getLandingPageContent()` is called, then a `LandingPageDTO` is returned with an empty sections array and default branding values.
- 4.7 Given a visitor accessing `GET /`, when landing page sections and branding data exist, then the page renders with dynamic content from the Branding module including all visible sections and featured courses.

## Requirement 5: Platform Branding Settings

### Description
The admin can configure platform-wide branding settings including site name, tagline, logo URL, favicon URL, primary and secondary colors, and footer text. Only one branding record exists (singleton pattern). Branding data is shared across all pages via Inertia middleware. All branding data is cached in Redis with a 5-minute TTL and invalidated on updates.

### Acceptance Criteria

- 5.1 Given no platform branding record exists, when `BrandingService::getPlatformBranding()` is called, then default values are returned (site_name: 'GrowthPedia', primary_color: '#3B82F6', secondary_color: '#1E40AF').
- 5.2 Given an admin user, when they update platform branding via `PUT /admin/branding/platform` with valid data, then the branding record is updated (or created if it doesn't exist) and the updated DTO is returned.
- 5.3 Given a primary_color value that is not a valid hex color code (e.g., 'red', '#GGG', '3B82F6'), when the admin attempts to update branding, then validation fails with an appropriate error message.
- 5.4 Given a secondary_color value that is not a valid hex color code, when the admin attempts to update branding, then validation fails.
- 5.5 Given platform branding data exists, when any page is loaded via Inertia, then the shared data includes `branding.site_name`, `branding.logo_url`, and `branding.primary_color` from the `HandleInertiaRequests` middleware.
- 5.6 Given branding data is cached, when the admin updates any branding field, then the cache keys `branding:profile`, `branding:landing`, and `branding:platform` are invalidated and subsequent reads return the updated data.
- 5.7 Given a non-admin user, when they attempt to access any `/admin/branding/*` route, then the request is rejected with a 403 Forbidden response.

## Requirement 6: Catalog Module Updates

### Description
The Catalog module is updated to source the creator/instructor name from the Branding module instead of the per-course instructor relationship. The `CatalogCourseDTO` and `CatalogCourseDetailDTO` replace `instructorName` with `creatorName` and `instructorBio` with `creatorBio`. The `CatalogService` no longer eager-loads the `instructor` relationship on course queries.

### Acceptance Criteria

- 6.1 Given published courses exist and a creator profile is configured, when `CatalogService::browse()` is called, then each `CatalogCourseDTO` in the result contains a `creatorName` field sourced from `BrandingServiceInterface::getCreatorName()`, not from the course's instructor relationship.
- 6.2 Given a published course and a creator profile, when `CatalogService::getCourseDetail()` is called, then the `CatalogCourseDetailDTO` contains `creatorName` and `creatorBio` sourced from the Branding module.
- 6.3 Given the `CatalogService` implementation, when course queries are executed, then the `instructor` relationship is not eager-loaded (no `with('instructor')` in queries).
- 6.4 Given the catalog search functionality, when a user searches for courses, then results include `creatorName` from branding (same for all courses) instead of per-course instructor names.

## Requirement 7: Branding Module Structure

### Description
A new `Branding` module is created following the existing modular monolith patterns. It includes Models, Contracts, Services, Controllers, DTOs, Routes, Providers, and Requests directories. The module registers its service provider automatically via the existing `ModuleServiceProvider` auto-discovery mechanism. Cross-module communication uses service interfaces (contracts), not direct model access.

### Acceptance Criteria

- 7.1 Given the Branding module is created, when the application boots, then `BrandingServiceProvider` is auto-discovered and registered by `ModuleServiceProvider`.
- 7.2 Given the Branding module, when its structure is inspected, then it contains the standard directories: Models, Contracts, Services, Controllers, DTOs, Routes, Providers, Requests.
- 7.3 Given the Branding module's service provider, when it registers bindings, then `BrandingServiceInterface` is bound to `BrandingService` in the container.
- 7.4 Given other modules (Catalog, Certificate) need the creator name, when they access it, then they use `BrandingServiceInterface::getCreatorName()` via dependency injection, not direct access to `CreatorProfile` model.
- 7.5 Given the Branding module's routes, when they are loaded, then admin routes are prefixed with `/admin/branding` and protected by `auth` + `admin` middleware, and public routes (creator profile page) are accessible without authentication.

## Requirement 8: Database Migrations

### Description
Database migrations are created to support the pivot. New tables (`creator_profiles`, `landing_page_sections`, `platform_brandings`) are created in Phase 1. Existing tables (`courses`, `users`) are modified in Phase 2. Migrations are designed to be data-safe — no data loss occurs during the transition.

### Acceptance Criteria

- 8.1 Given the Phase 1 migrations run, when the database schema is inspected, then tables `creator_profiles`, `landing_page_sections`, and `platform_brandings` exist with the correct columns and constraints.
- 8.2 Given the `creator_profiles` table, when its schema is inspected, then `user_id` has a unique constraint and a foreign key to `users`.
- 8.3 Given the Phase 2 migration for courses, when it runs on a database with existing courses, then the `instructor_id` column is renamed to `created_by` and all existing data values are preserved.
- 8.4 Given the Phase 2 migration for users, when it runs, then all users with role 'instructor' are updated to role 'admin' before the enum constraint is changed.
- 8.5 Given the Phase 2 migration for users, when it completes, then the role column only accepts values 'learner' and 'admin'.
- 8.6 Given courses that reference deleted users (orphaned courses), when the migration runs, then orphaned courses are reassigned to the first admin user and a warning is logged.

## Requirement 9: Frontend Updates

### Description
Frontend React/TypeScript components are updated to reflect the pivot. New pages are created for branding management (admin) and creator profile (public). Existing pages are updated to remove instructor references and use creator branding data. TypeScript types are updated to match the new DTOs.

### Acceptance Criteria

- 9.1 Given the admin is on the branding settings page, when they navigate to `/admin/branding`, then they see tabs or sections for Creator Profile, Landing Page Editor, and Platform Branding settings.
- 9.2 Given the admin is editing the creator profile, when they fill in display name, bio, expertise, social links, and featured courses and submit, then the form saves successfully and shows a success message.
- 9.3 Given a visitor navigates to `/creator`, when the creator profile exists, then the page displays the creator's display name, bio, avatar, expertise, social links, and featured courses.
- 9.4 Given the `Landing.tsx` page, when it renders, then it uses dynamic content from the Branding module (sections, branding, featured courses) instead of static content.
- 9.5 Given the Catalog pages (`CatalogIndex.tsx`, `CourseDetail.tsx`), when they render course cards, then they display `creatorName` instead of `instructorName`.
- 9.6 Given the Course management pages (`CourseList.tsx`, `CourseCreate.tsx`, `CourseEdit.tsx`), when they render, then there are no references to 'instructor' in the UI — only admin-oriented labels.
- 9.7 Given the TypeScript types in `resources/js/Types`, when they are inspected, then `instructorId`, `instructorName`, and `instructorBio` fields are replaced with `createdBy`, `creatorName`, and `creatorBio` respectively.

## Requirement 10: Existing Module Preservation

### Description
The Subscription, Payment, Progress, Certificate, Discussion, and Lesson modules remain functionally unchanged. No modifications are made to subscription logic, payment processing, revenue model, progress tracking, certificate generation, or discussion threads. Existing tests for these modules continue to pass.

### Acceptance Criteria

- 10.1 Given the pivot is complete, when the existing Subscription module tests are run, then all tests pass without modification.
- 10.2 Given the pivot is complete, when the existing Payment module tests are run, then all tests pass without modification.
- 10.3 Given the pivot is complete, when the existing Progress module tests are run, then all tests pass without modification.
- 10.4 Given the pivot is complete, when the existing Discussion module tests are run, then all tests pass without modification.
- 10.5 Given the pivot is complete, when the `SubscriptionService::subscribe()` method is called, then it processes payment and creates a subscription exactly as before — no changes to the flow.
- 10.6 Given the pivot is complete, when `CertificateService::generateCertificate()` is called, then it generates a certificate using the creator name from the Branding module (the only change is the name source, not the generation logic).
