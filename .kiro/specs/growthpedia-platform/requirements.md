# Requirements Document

## Introduction

GrowthPedia is an online learning platform (mini IxDF clone) that enables organizations to deliver structured courses with lessons, manage memberships and subscriptions, track learner progress, generate certificates upon course completion, facilitate discussions, and provide an admin panel for content and user management. The platform integrates payment processing for subscription billing. It is built as a modular monolith using Laravel (backend) and React (frontend) in a monorepo structure.

## Glossary

- **Platform**: The GrowthPedia online learning application as a whole
- **Learner**: A registered user who consumes courses and lessons
- **Instructor**: A registered user who creates and manages course content
- **Admin**: A privileged user who manages the platform, users, courses, and subscriptions
- **Course**: A structured collection of lessons organized around a specific topic
- **Lesson**: An individual unit of learning content within a course, containing text, video, or other media
- **Module**: A logical grouping of lessons within a course
- **Membership_Plan**: A subscription tier that defines access levels, pricing, and billing frequency
- **Subscription**: An active billing agreement between a Learner and a Membership_Plan
- **Progress_Tracker**: The component that records and calculates a Learner's advancement through course content
- **Certificate**: A verifiable document generated upon successful course completion
- **Discussion_Thread**: A conversation thread attached to a lesson or course for Learner and Instructor interaction
- **Comment**: An individual message within a Discussion_Thread
- **Payment_Gateway**: The external payment processing service (e.g., Stripe, Midtrans) used for subscription billing
- **Admin_Panel**: The administrative interface for managing platform content, users, and settings
- **Enrollment**: The association between a Learner and a Course, created when a Learner starts a course

## Requirements

### Requirement 1: Course Creation and Management

**User Story:** As an Instructor, I want to create and organize courses with modules and lessons, so that I can deliver structured learning content to Learners.

#### Acceptance Criteria

1. WHEN an Instructor submits a new course with a title, description, and category, THE Platform SHALL create the Course and assign it a unique identifier
2. WHEN an Instructor adds a Module to a Course, THE Platform SHALL associate the Module with the Course and preserve the specified display order
3. WHEN an Instructor adds a Lesson to a Module, THE Platform SHALL store the Lesson content (text, video URL, or embedded media) and associate the Lesson with the Module in the specified order
4. WHEN an Instructor updates a Course, Module, or Lesson, THE Platform SHALL persist the changes and reflect the updated content to enrolled Learners within 5 seconds of page refresh
5. WHEN an Instructor publishes a Course, THE Platform SHALL make the Course visible and accessible to Learners with an active Subscription
6. IF an Instructor attempts to publish a Course with zero Lessons, THEN THE Platform SHALL reject the publish action and display an error message stating the minimum lesson requirement
7. WHEN an Instructor unpublishes a Course, THE Platform SHALL hide the Course from the course catalog while preserving existing Enrollment and Progress_Tracker data

### Requirement 2: Lesson Content Delivery

**User Story:** As a Learner, I want to access lesson content in a structured sequence, so that I can learn topics in a logical progression.

#### Acceptance Criteria

1. WHEN a Learner opens a Lesson, THE Platform SHALL display the Lesson content including text, video, and any embedded media
2. WHEN a Learner completes a Lesson, THE Platform SHALL mark the Lesson as completed in the Progress_Tracker and unlock the next Lesson in the Module sequence
3. WHILE a Learner has an active Subscription, THE Platform SHALL grant access to all published Course content included in the Learner's Membership_Plan
4. IF a Learner attempts to access a Lesson without an active Subscription, THEN THE Platform SHALL deny access and display a prompt to subscribe or renew
5. THE Platform SHALL display Lessons in the order defined by the Instructor within each Module

### Requirement 3: Membership Plan Configuration

**User Story:** As an Admin, I want to configure membership plans with different pricing tiers, so that the platform can offer flexible subscription options to Learners.

#### Acceptance Criteria

1. WHEN an Admin creates a Membership_Plan, THE Platform SHALL store the plan name, description, price, billing frequency (monthly or yearly), and the set of accessible Courses
2. WHEN an Admin updates a Membership_Plan, THE Platform SHALL apply the changes to new Subscriptions while preserving existing Subscription terms until renewal
3. WHEN an Admin deactivates a Membership_Plan, THE Platform SHALL prevent new Subscriptions to that plan while allowing existing Subscribers to continue until their current billing period ends
4. THE Platform SHALL support a minimum of three concurrent Membership_Plans
5. IF an Admin attempts to delete a Membership_Plan with active Subscriptions, THEN THE Platform SHALL reject the deletion and display the count of active Subscriptions on that plan

### Requirement 4: Subscription and Billing

**User Story:** As a Learner, I want to subscribe to a membership plan and manage my billing, so that I can access course content continuously.

#### Acceptance Criteria

1. WHEN a Learner selects a Membership_Plan and submits payment details, THE Platform SHALL create a Subscription by sending a charge request to the Payment_Gateway
2. WHEN the Payment_Gateway confirms a successful payment, THE Platform SHALL activate the Subscription and grant the Learner access to the Courses included in the Membership_Plan
3. WHEN a Subscription reaches its renewal date, THE Platform SHALL initiate an automatic charge through the Payment_Gateway
4. IF the Payment_Gateway returns a payment failure during renewal, THEN THE Platform SHALL notify the Learner via email and provide a 7-day grace period before suspending access
5. WHEN a Learner cancels a Subscription, THE Platform SHALL stop future billing and maintain access until the end of the current billing period
6. IF a Learner's Subscription expires without renewal, THEN THE Platform SHALL revoke access to Subscription-gated content while preserving the Learner's Progress_Tracker data and Enrollment records
7. WHEN a Learner upgrades or downgrades a Membership_Plan, THE Platform SHALL prorate the billing amount for the remaining period and adjust Course access immediately

### Requirement 5: Progress Tracking

**User Story:** As a Learner, I want to track my progress through courses, so that I can see how much I have completed and what remains.

#### Acceptance Criteria

1. WHEN a Learner completes a Lesson, THE Progress_Tracker SHALL update the Learner's completion percentage for the associated Course
2. THE Progress_Tracker SHALL calculate course completion percentage as the number of completed Lessons divided by the total number of Lessons in the Course, multiplied by 100
3. WHEN a Learner opens a Course dashboard, THE Platform SHALL display the current completion percentage, the count of completed Lessons, and the count of remaining Lessons
4. WHEN a Learner resumes a Course, THE Platform SHALL navigate the Learner to the first incomplete Lesson in the Module sequence
5. THE Platform SHALL persist Progress_Tracker data across sessions, so that a Learner's progress is retained after logging out and logging back in
6. IF a Lesson is removed from a published Course, THEN THE Progress_Tracker SHALL recalculate the completion percentage for all affected Enrollments

### Requirement 6: Certificate Generation

**User Story:** As a Learner, I want to receive a certificate when I complete a course, so that I can demonstrate my achievement.

#### Acceptance Criteria

1. WHEN a Learner's course completion percentage reaches 100%, THE Platform SHALL automatically generate a Certificate for the Learner
2. THE Certificate SHALL contain the Learner's full name, the Course title, the completion date, and a unique verification code
3. WHEN a Learner requests to download a Certificate, THE Platform SHALL provide the Certificate as a PDF document
4. WHEN any user submits a verification code on the verification page, THE Platform SHALL confirm whether the Certificate is valid and display the associated Learner name, Course title, and completion date
5. IF a Learner has not completed all Lessons in a Course, THEN THE Platform SHALL not generate a Certificate for that Course
6. THE Platform SHALL store all generated Certificates and make them accessible from the Learner's profile page

### Requirement 7: Discussion and Comments

**User Story:** As a Learner, I want to participate in discussions on lessons, so that I can ask questions and interact with other Learners and Instructors.

#### Acceptance Criteria

1. WHEN a Learner or Instructor submits a Comment on a Lesson, THE Platform SHALL create a Discussion_Thread entry associated with that Lesson and display the Comment with the author's name and timestamp
2. WHEN a user submits a reply to an existing Comment, THE Platform SHALL nest the reply under the parent Comment in the Discussion_Thread
3. WHILE a Learner has an active Subscription, THE Platform SHALL allow the Learner to create Comments and replies in Discussion_Threads
4. IF a Learner's Subscription is inactive, THEN THE Platform SHALL allow the Learner to read existing Discussion_Threads but prevent the Learner from posting new Comments
5. WHEN an Instructor or Admin flags a Comment as inappropriate, THE Platform SHALL hide the Comment from public view and notify the Comment author via email
6. THE Platform SHALL display Comments in chronological order within each Discussion_Thread
7. WHEN a Learner or Instructor edits a Comment, THE Platform SHALL update the Comment content and display an "edited" indicator with the edit timestamp

### Requirement 8: Admin Panel — User Management

**User Story:** As an Admin, I want to manage users and their roles, so that I can control access and maintain platform integrity.

#### Acceptance Criteria

1. WHEN an Admin accesses the Admin_Panel, THE Platform SHALL display a paginated list of all registered users with their roles, Subscription status, and registration date
2. WHEN an Admin assigns a role (Learner, Instructor, or Admin) to a user, THE Platform SHALL update the user's permissions immediately
3. WHEN an Admin suspends a user account, THE Platform SHALL revoke the user's access to the Platform and display a suspension notice upon login attempt
4. WHEN an Admin searches for a user by name or email, THE Platform SHALL return matching results within 2 seconds
5. IF an Admin attempts to suspend the only remaining Admin account, THEN THE Platform SHALL reject the action and display a warning that at least one Admin account is required

### Requirement 9: Admin Panel — Content and Analytics

**User Story:** As an Admin, I want to view platform analytics and manage content, so that I can make informed decisions about course offerings and platform health.

#### Acceptance Criteria

1. WHEN an Admin opens the analytics dashboard, THE Platform SHALL display total Learner count, active Subscription count, total Course count, and total revenue for the selected date range
2. WHEN an Admin selects a Course in the Admin_Panel, THE Platform SHALL display the Enrollment count, average completion percentage, and average rating for that Course
3. WHEN an Admin filters analytics by date range, THE Platform SHALL recalculate and display metrics for the specified period within 5 seconds
4. THE Admin_Panel SHALL provide the ability to export analytics data as a CSV file
5. WHEN an Admin reviews flagged Comments, THE Platform SHALL display a list of all flagged Comments with the flag reason, author, and associated Lesson

### Requirement 10: Payment Integration

**User Story:** As a platform operator, I want to integrate with a payment gateway, so that the platform can securely process subscription payments.

#### Acceptance Criteria

1. WHEN the Platform sends a charge request to the Payment_Gateway, THE Platform SHALL transmit payment data over an encrypted (TLS 1.2 or higher) connection
2. THE Platform SHALL not store raw credit card numbers or CVV codes on Platform servers; the Platform SHALL delegate card storage to the Payment_Gateway's tokenization service
3. WHEN the Payment_Gateway sends a webhook notification for a payment event (success, failure, or refund), THE Platform SHALL process the webhook, verify its signature, and update the corresponding Subscription status
4. IF the Payment_Gateway is unreachable during a payment request, THEN THE Platform SHALL retry the request up to 3 times with exponential backoff and notify the Learner if all retries fail
5. WHEN a refund is initiated by an Admin, THE Platform SHALL send a refund request to the Payment_Gateway and update the Subscription and billing records upon confirmation
6. THE Platform SHALL log all payment transactions with the transaction ID, amount, currency, status, and timestamp for auditing purposes

### Requirement 11: Authentication and Authorization

**User Story:** As a user, I want to securely register and log in, so that my account and data are protected.

#### Acceptance Criteria

1. WHEN a user submits a registration form with a valid email and password, THE Platform SHALL create a new Learner account and send an email verification link
2. WHEN a user clicks the email verification link within 24 hours, THE Platform SHALL activate the account
3. IF a user submits a login request with invalid credentials, THEN THE Platform SHALL reject the login and display a generic error message without revealing whether the email or password was incorrect
4. WHEN a user submits a valid login request, THE Platform SHALL issue an authenticated session token
5. IF a user fails to log in 5 times consecutively within 15 minutes, THEN THE Platform SHALL lock the account for 30 minutes and notify the user via email
6. WHEN a user requests a password reset, THE Platform SHALL send a password reset link valid for 1 hour to the registered email address
7. THE Platform SHALL enforce role-based access control, restricting Learner, Instructor, and Admin actions to their respective permissions

### Requirement 12: Course Catalog and Search

**User Story:** As a Learner, I want to browse and search for courses, so that I can find content relevant to my learning goals.

#### Acceptance Criteria

1. WHEN a Learner opens the course catalog, THE Platform SHALL display a paginated list of all published Courses with title, description summary, Instructor name, and category
2. WHEN a Learner submits a search query, THE Platform SHALL return Courses matching the query against Course title, description, and category within 2 seconds
3. WHEN a Learner applies a category filter, THE Platform SHALL display only Courses belonging to the selected category
4. WHEN a Learner selects a Course from the catalog, THE Platform SHALL display the Course detail page including the full description, Module and Lesson outline, Instructor bio, Enrollment count, and average rating
5. THE Platform SHALL sort the course catalog by most recently published date by default
