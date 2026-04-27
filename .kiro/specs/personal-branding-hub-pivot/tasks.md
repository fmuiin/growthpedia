# Tasks: Personal Branding Hub Pivot

## Phase 1: Non-Breaking Additions (Branding Module)

- [x] 1. Create Branding module directory structure
  - [x] 1.1 Create `app/Modules/Branding` with standard subdirectories: Models, Contracts, Services, Controllers, DTOs, Routes, Providers, Requests, Actions, Events, Exceptions, Listeners, Middleware, Policies, Tests/Unit, Tests/Integration, Tests/Property
  - [x] 1.2 Create `BrandingServiceProvider` in `app/Modules/Branding/Providers` that binds `BrandingServiceInterface` to `BrandingService` and loads routes from `Routes/web.php`
  - [x] 1.3 Verify auto-discovery works — `ModuleServiceProvider` picks up `BrandingServiceProvider` on application boot

- [x] 2. Create database migrations for new tables
  - [x] 2.1 Create migration for `creator_profiles` table with columns: id, user_id (unique FK to users), display_name, bio, avatar_url, expertise, social_links (JSON), featured_course_ids (JSON), timestamps
  - [x] 2.2 Create migration for `landing_page_sections` table with columns: id, section_type, title, subtitle, content, image_url, cta_text, cta_url, sort_order, is_visible, metadata (JSON), timestamps
  - [x] 2.3 Create migration for `platform_brandings` table with columns: id, site_name, tagline, logo_url, favicon_url, primary_color, secondary_color, footer_text, metadata (JSON), timestamps
  - [x] 2.4 Run migrations and verify all three tables are created with correct columns and constraints

- [x] 3. Create Branding module models
  - [x] 3.1 Create `CreatorProfile` model with fillable fields, casts (social_links → array, featured_course_ids → array), and `user()` BelongsTo relationship
  - [x] 3.2 Create `LandingPageSection` model with fillable fields and casts (sort_order → integer, is_visible → boolean, metadata → array)
  - [x] 3.3 Create `PlatformBranding` model with fillable fields and casts (metadata → array)

- [x] 4. Create Branding module DTOs
  - [x] 4.1 Create `CreatorProfileDTO` (readonly) with fields: id, userId, displayName, bio, avatarUrl, expertise, socialLinks, featuredCourseIds
  - [x] 4.2 Create `UpdateCreatorProfileDTO` (readonly) with all nullable fields: displayName, bio, avatarUrl, expertise, socialLinks, featuredCourseIds
  - [x] 4.3 Create `LandingPageSectionDTO` (readonly) with fields: id, sectionType, title, subtitle, content, imageUrl, ctaText, ctaUrl, sortOrder, isVisible, metadata
  - [x] 4.4 Create `LandingPageDTO` (readonly) with fields: branding (PlatformBrandingDTO), sections (array of LandingPageSectionDTO), creatorProfile (CreatorProfileDTO|null), featuredCourses (array)
  - [x] 4.5 Create `UpdateLandingPageDTO` (readonly) with fields: sections (array of section data to upsert)
  - [x] 4.6 Create `PlatformBrandingDTO` (readonly) with fields: id, siteName, tagline, logoUrl, faviconUrl, primaryColor, secondaryColor, footerText
  - [x] 4.7 Create `UpdatePlatformBrandingDTO` (readonly) with all nullable fields: siteName, tagline, logoUrl, faviconUrl, primaryColor, secondaryColor, footerText

- [x] 5. Create Branding service contract and implementation
  - [x] 5.1 Create `BrandingServiceInterface` in Contracts with methods: getCreatorProfile, updateCreatorProfile, getLandingPageContent, updateLandingPageContent, getPlatformBranding, updatePlatformBranding, getCreatorName
  - [x] 5.2 Implement `BrandingService::getCreatorProfile()` — auto-initializes from first admin user if no profile exists, caches result
  - [x] 5.3 Implement `BrandingService::updateCreatorProfile()` — validates featured_course_ids are published, updates profile, invalidates cache
  - [x] 5.4 Implement `BrandingService::getLandingPageContent()` — assembles LandingPageDTO from sections (visible, sorted), branding, creator profile, and featured courses; caches result with 300s TTL
  - [x] 5.5 Implement `BrandingService::updateLandingPageContent()` — upserts landing page sections, invalidates cache
  - [x] 5.6 Implement `BrandingService::getPlatformBranding()` — returns singleton branding record or defaults, caches result
  - [x] 5.7 Implement `BrandingService::updatePlatformBranding()` — creates or updates singleton record, invalidates cache
  - [x] 5.8 Implement `BrandingService::getCreatorName()` — returns display_name from creator profile (used by Catalog and Certificate modules)

- [x] 6. Create Branding module form requests
  - [x] 6.1 Create `UpdateCreatorProfileRequest` with validation rules: display_name (string, max:255), bio (nullable, string, max:5000), avatar_url (nullable, url, max:500), expertise (nullable, string, max:255), social_links (nullable, array with known keys), featured_course_ids (nullable, array of existing published course IDs)
  - [x] 6.2 Create `UpdateLandingPageSectionRequest` with validation rules: section_type (required, in:hero,about,featured_courses,testimonials,cta), title (nullable, string, max:255), subtitle (nullable, string), content (nullable, string), image_url (nullable, url, max:500), cta_text (nullable, string, max:100), cta_url (nullable, url, max:500), sort_order (integer), is_visible (boolean)
  - [x] 6.3 Create `UpdatePlatformBrandingRequest` with validation rules: site_name (string, max:255), tagline (nullable, string, max:500), logo_url (nullable, url, max:500), favicon_url (nullable, url, max:500), primary_color (regex for hex code), secondary_color (regex for hex code), footer_text (nullable, string)

- [x] 7. Create Branding module controllers and routes
  - [x] 7.1 Create `BrandingController` with methods: showProfile, updateProfile, showLandingEditor, updateLandingSection, createLandingSection, deleteLandingSection, showPlatformBranding, updatePlatformBranding
  - [x] 7.2 Create `CreatorProfileController` with method: show (public page at GET /creator)
  - [x] 7.3 Create admin routes in `Routes/web.php`: GET/PUT `/admin/branding/profile`, GET/POST/PUT/DELETE `/admin/branding/landing-sections`, GET/PUT `/admin/branding/platform` — all behind auth + admin middleware
  - [x] 7.4 Create public route: GET `/creator` — accessible without authentication

- [x] 8. Create Branding module frontend pages
  - [x] 8.1 Create TypeScript types for Branding module: `CreatorProfile`, `LandingPageSection`, `PlatformBranding`, `LandingPageData` in `resources/js/Types`
  - [x] 8.2 Create `Admin/CreatorProfileEdit.tsx` page — form for editing display name, bio, avatar URL, expertise, social links, featured course selection
  - [x] 8.3 Create `Admin/LandingPageEditor.tsx` page — section list with drag-to-reorder, add/edit/delete sections, visibility toggles
  - [x] 8.4 Create `Admin/PlatformBrandingEdit.tsx` page — form for site name, tagline, logo URL, favicon URL, color pickers, footer text
  - [x] 8.5 Create `CreatorProfile.tsx` public page — displays creator bio, avatar, expertise, social links, and featured courses
  - [x] 8.6 Add navigation links to admin sidebar for branding management pages

- [x] 9. Write tests for Branding module
  - [x] 9.1 Write unit tests for `BrandingService` — test getCreatorProfile (auto-init and existing), updateCreatorProfile (valid and invalid featured courses), getLandingPageContent (ordering, visibility filtering, empty state), getPlatformBranding (defaults and existing), updatePlatformBranding, getCreatorName
  - [x] 9.2 Write unit tests for form request validation — UpdateCreatorProfileRequest, UpdateLandingPageSectionRequest (valid section types, invalid types), UpdatePlatformBrandingRequest (hex color validation)
  - [x] 9.3 Write integration tests — admin can CRUD creator profile via HTTP, admin can CRUD landing sections via HTTP, admin can update platform branding via HTTP, non-admin gets 403 on all branding routes, public /creator page renders correctly
  - [x] 9.4 Write property-based tests with Eris — for any valid UpdateCreatorProfileDTO the update-then-read cycle returns updated values; for any set of sections with unique sort_orders getLandingPageContent returns them sorted ascending; singleton constraint holds for creator_profiles and platform_brandings

## Phase 2: Instructor Role Removal (Breaking Changes)

- [x] 10. Create database migrations for instructor removal
  - [x] 10.1 Create migration to promote all instructor users to admin: `UPDATE users SET role = 'admin' WHERE role = 'instructor'`
  - [x] 10.2 Create migration to rename `courses.instructor_id` to `courses.created_by` (data-preserving column rename)
  - [x] 10.3 Create migration to update users role enum constraint: drop old constraint, add new constraint allowing only 'learner' and 'admin'; include orphaned course handling (reassign to first admin)
  - [x] 10.4 Run migrations and verify: zero instructor users, all courses have valid created_by, role constraint enforced

- [x] 11. Update Course module for single-creator ownership
  - [x] 11.1 Update `Course` model: rename `instructor_id` in fillable to `created_by`, rename `instructor()` relationship to `creator()`, update any references
  - [x] 11.2 Update `CreateCourseDTO`: remove `instructorId` field, keep only title, description, category
  - [x] 11.3 Update `CourseDTO`: replace `instructorId` with `createdBy`
  - [x] 11.4 Update `CourseDetailDTO`: remove `instructorId` and `instructorName` fields, add `createdBy`
  - [x] 11.5 Update `CourseService::createCourse()`: set `created_by` from `Auth::id()` instead of `$dto->instructorId`
  - [x] 11.6 Update `CourseService::getCourseWithStructure()`: remove instructor eager-loading and instructorName from CourseDetailDTO construction
  - [x] 11.7 Simplify `CoursePolicy`: all methods check only `$user->role === 'admin'`, remove `$course->instructor_id === $user->id` checks
  - [x] 11.8 Update `CourseController::index()`: list all courses for admin instead of filtering by `instructor_id`
  - [x] 11.9 Update `CourseController::store()`: remove `instructorId` from CreateCourseDTO construction
  - [x] 11.10 Update Course `Routes/web.php`: change middleware from `role:instructor,admin` to `role:admin`

- [x] 12. Update User module for simplified roles
  - [x] 12.1 Update `UserService::VALID_ROLES` constant from `['learner', 'instructor', 'admin']` to `['learner', 'admin']`
  - [x] 12.2 Update `AssignRoleRequest` validation rule from `'in:learner,instructor,admin'` to `'in:learner,admin'`

- [x] 13. Update Catalog module to use Branding for creator name
  - [x] 13.1 Add `BrandingServiceInterface` dependency to `CatalogService` constructor via dependency injection
  - [x] 13.2 Update `CatalogService::browse()`: remove `with('instructor')` eager-load, use `$this->brandingService->getCreatorName()` for creatorName in DTOs
  - [x] 13.3 Update `CatalogService::search()`: remove `with('instructor')` eager-load, use branding service for creator name
  - [x] 13.4 Update `CatalogService::getCourseDetail()`: remove `with('instructor')` from query, source creatorName and creatorBio from BrandingService
  - [x] 13.5 Update `CatalogCourseDTO`: rename `instructorName` to `creatorName`
  - [x] 13.6 Update `CatalogCourseDetailDTO`: rename `instructorName` to `creatorName`, rename `instructorBio` to `creatorBio`

- [x] 14. Update affected frontend components and TypeScript types
  - [x] 14.1 Update TypeScript types: replace `instructorId`/`instructorName`/`instructorBio` with `createdBy`/`creatorName`/`creatorBio` in Course and Catalog types
  - [x] 14.2 Update `CourseList.tsx`: remove instructor column/references, show all courses for admin
  - [x] 14.3 Update `CourseCreate.tsx`: remove any instructor-related fields
  - [x] 14.4 Update `CourseEdit.tsx`: remove instructor name display, update DTO references
  - [x] 14.5 Update `CatalogIndex.tsx`: replace `instructorName` with `creatorName` in course cards
  - [x] 14.6 Update `CourseDetail.tsx` (Catalog): replace `instructorName`/`instructorBio` with `creatorName`/`creatorBio`
  - [x] 14.7 Update `Admin/UserManagement.tsx`: remove 'instructor' from role assignment options

- [x] 15. Update existing tests for Phase 2 changes
  - [x] 15.1 Update Course module unit tests: adjust for removed instructorId in CreateCourseDTO, simplified policy checks, admin-only course listing
  - [x] 15.2 Update Course module integration tests: verify admin-only course creation, learner rejection, updated middleware
  - [x] 15.3 Update Catalog module tests: verify creatorName sourced from BrandingService, no instructor eager-loading
  - [x] 15.4 Update User module tests: verify 'instructor' role assignment is rejected, VALID_ROLES updated
  - [x] 15.5 Update Admin module tests: verify role assignment dropdown only shows 'learner' and 'admin'
  - [x] 15.6 Run full test suite to verify no regressions in Subscription, Payment, Progress, Discussion, Certificate modules

## Phase 3: Landing Page and Branding Integration

- [x] 16.    - [x] 16.1 Create or update `LandingController` to assemble landing page data: call `BrandingService::getLandingPageContent()` and pass to Inertia render
  - [x] 16.2 Update `Landing.tsx` to accept and render dynamic props: branding, sections, creatorProfile, featuredCourses
  - [x] 16.3 Create `LandingSection` React component that renders different section types (hero, about, featured_courses, testimonials, cta) based on section_type
  - [x] 16.4 Create `FeaturedCourses` React component that displays a grid of featured course cards with creator name

- [x] 17. Add branding context to Inertia shared data
  - [x] 17.1 Update `HandleInertiaRequests` middleware to share branding data (site_name, logo_url, primary_color, secondary_color) on every page load via `BrandingServiceInterface`
  - [x] 17.2 Update the main layout component to use shared branding data for site title, logo, and theme colors

- [ ] 18. Update Certificate module for creator name
  - [ ] 18.1 Update certificate generation/template to source creator name from `BrandingServiceInterface::getCreatorName()` instead of course instructor name

- [ ] 19. Create database seeder for default branding data
  - [ ] 19.1 Create `BrandingSeeder` that seeds: default platform_brandings record (site_name: 'GrowthPedia', default colors), default landing_page_sections (hero, about, featured_courses sections with placeholder content)
  - [ ] 19.2 Register seeder in `DatabaseSeeder`

- [ ] 20. End-to-end testing and verification
  - [ ] 20.1 Write integration tests for landing page: seed branding data, GET /, verify Inertia response contains correct sections, branding, and featured courses
  - [ ] 20.2 Write integration tests for creator profile page: seed profile, GET /creator, verify response contains profile data and featured courses
  - [ ] 20.3 Write integration tests for branding shared data: verify HandleInertiaRequests shares branding on all pages
  - [ ] 20.4 Run the complete test suite (all modules) and verify zero failures
  - [ ] 20.5 Verify existing Subscription, Payment, Progress, Discussion module tests pass without modification
