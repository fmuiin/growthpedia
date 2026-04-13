# Implementation Plan: GrowthPedia Platform

## Overview

Implementasi GrowthPedia sebagai modular monolith Laravel + React/Inertia.js. Tasks disusun secara incremental — dimulai dari shared foundation, lalu per-module (models, services, controllers, pages), diakhiri dengan integrasi antar-module dan wiring. Setiap task mereferensikan requirements dan correctness properties dari design document.

## Tasks

- [ ] 1. Set up project structure, shared foundation, and module scaffolding
  - [x] 1.1 Initialize Laravel + React/Inertia.js monorepo structure
    - Install Laravel, Inertia.js, React, and TypeScript
    - Configure PostgreSQL, Redis connections in `.env`
    - Set up Pest PHP testing framework with Eris for property-based testing
    - Create base directory structure: `app/Modules/`, `app/Shared/`, `resources/js/Pages/`, `resources/js/Components/`
    - _Requirements: All (foundation)_

  - [x] 1.2 Create shared DTOs, value objects, and base classes
    - Create `app/Shared/DTOs/` with base DTO abstract class
    - Create shared value objects (DateRangeDTO, PaginationDTO)
    - Create base Service interface, base Action class, base Exception classes
    - Set up Laravel service provider for module registration
    - _Requirements: All (foundation)_

  - [x] 1.3 Scaffold all 10 module directories with standard structure
    - Create directory structure for each module: Course, Lesson, Subscription, Progress, Certificate, Discussion, User, Payment, Admin, Catalog
    - Each module gets: Controllers/, Models/, Services/, Actions/, DTOs/, Events/, Listeners/, Requests/, Policies/, Exceptions/, Routes/web.php, Tests/Unit/, Tests/Property/, Tests/Integration/
    - Register module service providers in `config/app.php`
    - _Requirements: All (foundation)_

- [ ] 2. Implement User module — authentication and authorization
  - [x] 2.1 Create User model, migrations, and DTOs
    - Create `users` table migration with all fields from design (role enum, email_verified_at, is_suspended, failed_login_attempts, locked_until)
    - Create User Eloquent model with casts and relationships
    - Create RegisterDTO, UserDTO, and related DTOs
    - _Requirements: 11.1, 11.7_

  - [x] 2.2 Implement UserService — registration, email verification, and login logic
    - Implement `register()`: create learner account, dispatch `UserRegistered` event, send verification email
    - Implement `verifyEmail()`: validate token within 24-hour window, activate account
    - Implement login logic: generic error on invalid credentials, track failed attempts, lock account after 5 failures in 15 minutes
    - Implement `lockAccount()` and `suspendUser()` with last-admin protection
    - Implement `assignRole()` for immediate permission update
    - Implement `searchUsers()` with name/email substring matching
    - Implement password reset with 1-hour token expiry
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7, 8.2, 8.3, 8.4, 8.5_

  - [x] 2.3 Create Auth controllers and Form Requests
    - Create RegisterController, LoginController, VerifyEmailController, ForgotPasswordController
    - Create Form Request validation classes for each endpoint
    - Set up role-based middleware (learner, instructor, admin)
    - Create authorization Policies for User module
    - _Requirements: 11.1, 11.3, 11.4, 11.6, 11.7_

  - [x] 2.4 Create React Auth pages
    - Create Login, Register, ForgotPassword, ResetPassword, VerifyEmail pages
    - Create TypeScript interfaces for User DTOs
    - Wire pages with Inertia.js routing
    - _Requirements: 11.1, 11.3, 11.4, 11.6_

  - [ ]* 2.5 Write property tests for User module
    - **Property 31: Email verification time window** — account activated only within 24 hours
    - **Property 32: Account locking after failed login attempts** — lock after 5 failures in 15 min, no lock otherwise
    - **Property 23: Role-based access control enforcement** — role assignment immediately updates permissions
    - **Property 24: User suspension revokes access** — suspended user denied access, last admin protected
    - **Property 25: User search returns matching results** — search returns all and only matching users
    - **Validates: Requirements 11.2, 11.5, 8.2, 8.3, 8.4, 8.5, 11.7**

  - [ ]* 2.6 Write unit tests for User module
    - Test registration creates learner account and dispatches verification email
    - Test invalid login returns generic error message
    - Test valid login issues session token
    - Test password reset generates time-limited token
    - _Requirements: 11.1, 11.3, 11.4, 11.6_

- [ ] 3. Implement Course and Lesson modules
  - [x] 3.1 Create Course, CourseModule, and Lesson models and migrations
    - Create `courses` table migration (instructor_id FK, title, description, category, status enum, published_at)
    - Create `course_modules` table migration (course_id FK, title, sort_order)
    - Create `lessons` table migration (course_module_id FK, title, content_type enum, content_body, video_url, sort_order)
    - Create Eloquent models with relationships and casts
    - Create all DTOs: CreateCourseDTO, UpdateCourseDTO, CourseDTO, CourseDetailDTO, CreateModuleDTO, ModuleDTO, CreateLessonDTO, LessonDTO
    - _Requirements: 1.1, 1.2, 1.3_

  - [x] 3.2 Implement CourseService
    - Implement `createCourse()`: validate input, create course with unique ID, assign instructor
    - Implement `addModule()`: associate module with course, preserve sort_order
    - Implement `addLesson()`: store lesson content, associate with module in sort_order
    - Implement `updateCourse()`: persist changes to course, module, or lesson
    - Implement `publishCourse()`: reject if zero lessons, set status to published, set published_at
    - Implement `unpublishCourse()`: hide from catalog, preserve enrollments and progress
    - Implement `deleteLessonFromPublishedCourse()`: remove lesson, dispatch `LessonRemovedFromCourse` event
    - Implement `getCourseWithStructure()`: return course with modules and lessons in sort_order
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7_

  - [x] 3.3 Create Course controllers, Form Requests, and Policies
    - Create CourseController (CRUD, publish/unpublish), ModuleController, LessonController
    - Create Form Request validation for each action
    - Create CoursePolicy restricting actions to course owner (instructor) and admins
    - Define routes in Course module's `Routes/web.php`
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7_

  - [x] 3.4 Create React Course pages and components
    - Create CourseCreate, CourseEdit pages for instructors
    - Create LessonView page for learners (text, video, mixed content display)
    - Create ModuleList, LessonPlayer components
    - Create TypeScript interfaces for Course/Lesson DTOs
    - _Requirements: 1.1, 1.4, 2.1, 2.5_

  - [ ]* 3.5 Write property tests for Course module
    - **Property 1: Course creation preserves all input data** — createCourse returns matching fields
    - **Property 2: Structural ordering is preserved** — modules and lessons returned in sort_order
    - **Property 3: Course visibility follows publish status** — only published courses in catalog
    - **Validates: Requirements 1.1, 1.2, 1.3, 1.5, 1.7, 2.5, 12.1**

  - [ ]* 3.6 Write unit tests for Course module
    - Test publish course with zero lessons is rejected
    - Test lesson content rendering returns correct content types
    - Test unpublish preserves enrollment data
    - _Requirements: 1.6, 2.1, 1.7_

- [x] 4. Checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [-] 5. Implement MembershipPlan and Subscription modules
  - [x] 5.1 Create MembershipPlan, CourseMembershipPlan, and Subscription models and migrations
    - Create `membership_plans` table migration (name, description, price, billing_frequency enum, is_active)
    - Create `course_membership_plan` pivot table migration
    - Create `subscriptions` table migration (user_id FK, membership_plan_id FK, status enum, starts_at, ends_at, grace_period_ends_at, cancelled_at, gateway_subscription_id)
    - Create Eloquent models with relationships
    - Create all DTOs: MembershipPlanDTO, SubscriptionDTO, PaymentTokenDTO
    - _Requirements: 3.1, 4.1_

  - [-] 5.2 Implement MembershipPlan management in Admin module
    - Implement create plan: store all fields (name, description, price, billing_frequency, course set)
    - Implement update plan: apply changes to new subscriptions only, preserve existing terms
    - Implement deactivate plan: prevent new subscriptions, allow existing to continue
    - Implement delete plan protection: reject if active subscriptions exist, return count
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

  - [~] 5.3 Implement SubscriptionService
    - Implement `subscribe()`: create subscription via payment gateway, activate on success
    - Implement `handleRenewal()`: auto-charge at renewal date, set 7-day grace period on failure
    - Implement `cancel()`: stop future billing, maintain access until period end
    - Implement `suspendExpired()`: revoke access, preserve progress and enrollment data
    - Implement `changePlan()`: calculate proration `(new_daily_rate - old_daily_rate) * remaining_days`, adjust access immediately
    - Implement `hasActiveSubscription()` and `getUserPlanCourseIds()` for access control checks
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_

  - [~] 5.4 Create Subscription controllers and React pages
    - Create SubscriptionController (subscribe, cancel, change plan)
    - Create MembershipPlanController for admin CRUD
    - Create Plans page (list available plans), Checkout page, ManageSubscription page
    - Create PlanCard, PaymentForm components
    - _Requirements: 3.1, 4.1, 4.5, 4.7_

  - [ ]* 5.5 Write property tests for MembershipPlan and Subscription
    - **Property 6: Membership plan creation stores all fields** — all input fields persisted and retrievable
    - **Property 7: Plan updates do not affect existing subscriptions** — changes apply only to new subs
    - **Property 8: Deactivated plans reject new subscriptions** — new subs rejected, existing continue
    - **Property 5: Subscription-based access control** — access iff active sub with matching plan
    - **Property 9: Grace period is exactly 7 days after renewal failure** — status and date correct
    - **Property 10: Cancellation preserves access until period end** — access maintained, no further billing
    - **Property 11: Expiration revokes access but preserves data** — access revoked, data intact
    - **Property 12: Proration calculation correctness** — prorated amount matches formula
    - **Validates: Requirements 3.1, 3.2, 3.3, 4.1–4.7, 2.3, 2.4**

  - [ ]* 5.6 Write unit tests for Subscription module
    - Test subscription creation flow with mocked payment gateway
    - Test subscription activation on payment success
    - _Requirements: 4.1, 4.2_

- [ ] 6. Implement Progress and Certificate modules
  - [ ] 6.1 Create Enrollment, LessonProgress, and Certificate models and migrations
    - Create `enrollments` table migration (user_id FK, course_id FK, enrolled_at, completion_percentage, completed_at, unique constraint on user_id+course_id)
    - Create `lesson_progress` table migration (enrollment_id FK, lesson_id FK, completed_at, unique constraint on enrollment_id+lesson_id)
    - Create `certificates` table migration (enrollment_id FK unique, user_id FK, course_id FK, verification_code unique, learner_name, course_title, completed_at, pdf_path)
    - Create Eloquent models with relationships
    - Create DTOs: ProgressDTO, CourseProgressDTO, CertificateDTO
    - _Requirements: 5.1, 5.2, 6.1, 6.2_

  - [ ] 6.2 Implement ProgressService
    - Implement `markLessonComplete()`: create LessonProgress record, recalculate completion percentage as `completed / total * 100`, dispatch `LessonCompleted` event
    - Implement `getCourseProgress()`: return completion percentage, completed count, remaining count
    - Implement `getNextLesson()`: find first incomplete lesson by module sort_order then lesson sort_order
    - Implement `recalculateForCourse()`: recalculate all enrollments when lesson removed (listen to `LessonRemovedFromCourse` event)
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_

  - [ ] 6.3 Implement CertificateService
    - Implement `generateCertificate()`: auto-generate on 100% completion (listen to `CourseCompleted` event), store learner_name, course_title, completion date, unique verification_code
    - Implement `verifyCertificate()`: lookup by verification_code, return learner name, course title, completion date
    - Implement `downloadPdf()`: generate PDF certificate document
    - Implement `getUserCertificates()`: list all certificates for a user's profile
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6_

  - [ ] 6.4 Create event listeners for Progress and Certificate
    - Create `LessonCompletedListener` in Progress module: update progress, check if course completed, dispatch `CourseCompleted` if 100%
    - Create `CourseCompletedListener` in Certificate module: generate certificate
    - Create `LessonRemovedFromCourseListener` in Progress module: recalculate affected enrollments
    - _Requirements: 5.1, 5.6, 6.1_

  - [ ] 6.5 Create Progress and Certificate controllers and React pages
    - Create ProgressController (course dashboard, resume course)
    - Create CertificateController (list, download, verify)
    - Create LearnerDashboard page with ProgressBar component
    - Create MyCertificates page, VerifyCertificate page
    - _Requirements: 5.3, 5.4, 6.3, 6.4, 6.6_

  - [ ]* 6.6 Write property tests for Progress module
    - **Property 4: Lesson completion advances progress correctly** — percentage = (M+1)/N * 100
    - **Property 13: Resume navigates to first incomplete lesson** — correct lesson by sort order
    - **Property 14: Lesson removal triggers correct recalculation** — recalculated excluding removed lesson
    - **Validates: Requirements 2.2, 5.1, 5.2, 5.4, 5.6**

  - [ ]* 6.7 Write property tests for Certificate module
    - **Property 15: Certificate generation at 100% completion** — generated iff 100%, contains all required fields
    - **Property 16: Certificate verification round trip** — lookup by code returns correct data
    - **Validates: Requirements 6.1, 6.2, 6.4, 6.5**

  - [ ]* 6.8 Write unit tests for Progress and Certificate
    - Test PDF certificate download generates valid PDF
    - Test progress persists across sessions
    - _Requirements: 6.3, 5.5_

- [ ] 7. Checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 8. Implement Discussion module
  - [ ] 8.1 Create Comment model and migration
    - Create `comments` table migration (lesson_id FK, user_id FK, parent_comment_id FK nullable, content, is_flagged, flag_reason, flagged_by FK nullable, is_edited, edited_at)
    - Create Comment Eloquent model with self-referencing relationship for nesting
    - Create DTOs: CommentDTO, PaginatedCommentsDTO
    - _Requirements: 7.1, 7.2_

  - [ ] 8.2 Implement DiscussionService
    - Implement `createComment()`: create comment with author name and timestamp, check active subscription (or instructor/admin role)
    - Implement `replyToComment()`: nest reply under parent comment with correct parent_comment_id
    - Implement `editComment()`: update content, set is_edited=true and edited_at timestamp
    - Implement `flagComment()`: set is_flagged=true, store reason and flagged_by, dispatch `CommentFlagged` event for email notification
    - Implement `getThreadForLesson()`: return comments in chronological order, exclude flagged from public view, paginated
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7_

  - [ ] 8.3 Create Discussion controllers and React components
    - Create CommentController (create, reply, edit, flag)
    - Create CommentThread, CommentForm, FlagButton components
    - Embed discussion in LessonView page
    - Enforce subscription check on comment creation (read-only for inactive)
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.7_

  - [ ]* 8.4 Write property tests for Discussion module
    - **Property 17: Comment creation includes author and timestamp** — correct author, timestamp, lesson association
    - **Property 18: Comment nesting preserves parent-child relationships** — correct parent_comment_id
    - **Property 19: Subscription status controls commenting ability** — active sub can post, inactive can only read
    - **Property 20: Flagged comments are hidden from public view** — not in public queries, visible in admin
    - **Property 21: Comments are ordered chronologically** — ascending by created_at
    - **Property 22: Comment editing sets edited indicator** — content updated, is_edited=true, edited_at set
    - **Validates: Requirements 7.1–7.7**

- [ ] 9. Implement Payment module
  - [ ] 9.1 Create PaymentTransaction model and migration
    - Create `payment_transactions` table migration (subscription_id FK, gateway_transaction_id, amount, currency default 'IDR', status enum, type enum, metadata json, created_at)
    - Create PaymentTransaction Eloquent model
    - Create DTOs: PaymentRequestDTO, PaymentResultDTO, RefundResultDTO
    - _Requirements: 10.6_

  - [ ] 9.2 Implement PaymentGateway interface and adapter
    - Create `PaymentGatewayInterface` contract
    - Implement gateway adapter (Stripe or Midtrans) with TLS 1.2+ enforcement
    - Implement `charge()`: send charge request, no raw card storage (use tokenization)
    - Implement `refund()`: send refund request, update subscription and billing records
    - Implement `verifyWebhookSignature()`: validate webhook payload signature
    - Implement `retryCharge()`: retry up to 3 times with exponential backoff, notify learner on all failures
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

  - [ ] 9.3 Implement webhook controller and payment event listeners
    - Create WebhookController: receive webhook, verify signature, process payment events (success, failure, refund)
    - Create `PaymentSucceededListener` in Subscription module: activate subscription
    - Create `PaymentFailedListener` in Subscription module: start grace period, email learner
    - Log all transactions with transaction_id, amount, currency, status, timestamp
    - _Requirements: 10.3, 10.5, 10.6, 4.2, 4.4_

  - [ ] 9.4 Wire payment into Subscription flow
    - Connect SubscriptionService.subscribe() to PaymentGateway.charge()
    - Connect SubscriptionService.handleRenewal() to PaymentGateway.retryCharge()
    - Connect admin refund action to PaymentGateway.refund()
    - _Requirements: 4.1, 4.3, 10.5_

  - [ ]* 9.5 Write property tests for Payment module
    - **Property 28: Webhook signature verification** — accept valid, reject invalid signatures
    - **Property 29: Payment retry with exponential backoff** — max 3 retries, increasing intervals
    - **Property 30: Payment transaction logging completeness** — all required fields logged
    - **Validates: Requirements 10.3, 10.4, 10.6**

- [ ] 10. Checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 11. Implement Catalog module
  - [ ] 11.1 Implement CatalogService and search
    - Implement `browse()`: paginated list of published courses with title, description summary, instructor name, category; default sort by published_at descending
    - Implement `search()`: match query against course title, description, and category; return results within 2 seconds
    - Implement category filter: return only courses in selected category
    - Implement `getCourseDetail()`: full description, module/lesson outline, instructor bio, enrollment count, average rating
    - Use Redis caching for catalog queries
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5_

  - [ ] 11.2 Create Catalog controllers and React pages
    - Create CatalogController (browse, search, detail)
    - Create CatalogIndex page with search bar, category filter, pagination
    - Create CourseDetail page with full course information
    - Create SearchResults component
    - _Requirements: 12.1, 12.2, 12.3, 12.4_

  - [ ]* 11.3 Write property tests for Catalog module
    - **Property 33: Catalog search matches title, description, and category** — all matching courses included, non-matching excluded
    - **Property 34: Category filter returns only matching courses** — only selected category returned
    - **Property 35: Default catalog sort is by most recent publication date** — descending published_at order
    - **Validates: Requirements 12.2, 12.3, 12.5**

- [ ] 12. Implement Admin module — user management and analytics
  - [ ] 12.1 Implement Admin user management
    - Create admin user list endpoint: paginated list with roles, subscription status, registration date
    - Wire role assignment, user suspension, and user search from UserService
    - Create admin middleware restricting access to admin role
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

  - [ ] 12.2 Implement AnalyticsService
    - Implement `getDashboardMetrics()`: total learner count, active subscription count, total course count, total revenue for date range
    - Implement `getCourseAnalytics()`: enrollment count, average completion percentage, average rating per course
    - Implement `exportCsv()`: export analytics data as CSV file
    - Implement `getFlaggedComments()`: paginated list of flagged comments with reason, author, lesson
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

  - [ ] 12.3 Create Admin React pages
    - Create UserManagement page (list, search, role assignment, suspend)
    - Create Analytics dashboard page with date range filter
    - Create ContentReview page for flagged comments
    - Create AdminLayout component
    - _Requirements: 8.1, 8.4, 9.1, 9.3, 9.5_

  - [ ]* 12.4 Write property tests for Admin/Analytics
    - **Property 26: Analytics aggregation correctness** — metrics match computed values for date range
    - **Property 27: Course analytics aggregation** — enrollment count, avg completion, avg rating correct
    - **Validates: Requirements 9.1, 9.2, 9.3**

  - [ ]* 12.5 Write unit tests for Admin module
    - Test admin user list pagination returns correct fields
    - Test flagged comments list returns correct data
    - _Requirements: 8.1, 9.5_

- [ ] 13. Integration wiring and cross-module event handling
  - [ ] 13.1 Wire all domain events and listeners
    - Register all events and listeners in EventServiceProvider
    - Wire `LessonCompleted` → ProgressModule (update tracker)
    - Wire `CourseCompleted` → CertificateModule (generate cert)
    - Wire `SubscriptionActivated` → UserModule (grant access)
    - Wire `SubscriptionSuspended` → UserModule (revoke access)
    - Wire `PaymentSucceeded` → SubscriptionModule (activate sub)
    - Wire `PaymentFailed` → SubscriptionModule (start grace period)
    - Wire `CommentFlagged` → notification (email author)
    - Wire `LessonRemovedFromCourse` → ProgressModule (recalculate)
    - Wire `UserRegistered` → send verification email
    - Wire `AccountLocked` → send lock notification email
    - _Requirements: 2.2, 4.2, 4.4, 5.1, 5.6, 6.1, 7.5, 11.1, 11.5_

  - [ ] 13.2 Wire subscription access control middleware
    - Create `EnsureActiveSubscription` middleware
    - Apply middleware to lesson access, comment creation routes
    - Implement access check: user has active subscription with plan that includes the course
    - Handle grace period access (still active during grace period)
    - _Requirements: 2.3, 2.4, 7.3, 7.4_

  - [ ] 13.3 Wire Inertia.js shared data and layouts
    - Configure Inertia shared data (auth user, subscription status, flash messages)
    - Create AppLayout, AdminLayout, GuestLayout components
    - Set up Inertia middleware for shared props
    - Configure error handling pages (403, 404, 500)
    - _Requirements: All (integration)_

  - [ ]* 13.4 Write integration tests
    - Test end-to-end subscription flow with sandbox payment gateway
    - Test full webhook receive → verify → process pipeline
    - Test email delivery for verification, password reset, notifications
    - Test certificate PDF generation and storage/retrieval
    - Test analytics CSV export generates valid CSV
    - _Requirements: 4.1, 4.2, 10.3, 11.1, 6.3, 9.4_

- [ ] 14. Final checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation between major implementation phases
- Property tests validate the 35 universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- Modules communicate through service interfaces and domain events — never direct model access
