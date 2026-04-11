<p align="center">
  <img src="https://img.shields.io/badge/GrowthPedia-Online%20Learning%20Platform-4f46e5?style=for-the-badge&logo=bookstack&logoColor=white" alt="GrowthPedia" />
</p>

<p align="center">
  A modern, open-source online learning platform built with Laravel, React, and Inertia.js.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php&logoColor=white" alt="PHP 8.3+" />
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white" alt="Laravel 13" />
  <img src="https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black" alt="React 19" />
  <img src="https://img.shields.io/badge/TypeScript-6-3178C6?logo=typescript&logoColor=white" alt="TypeScript 6" />
  <img src="https://img.shields.io/badge/Inertia.js-3-9553E9?logo=inertia&logoColor=white" alt="Inertia.js 3" />
  <img src="https://img.shields.io/badge/License-MIT-green" alt="MIT License" />
</p>

---

## About

GrowthPedia is a full-featured online learning platform that enables organizations to deliver structured courses, manage memberships and subscriptions, track learner progress, generate verifiable certificates, and facilitate discussions — all from a single, cohesive application.

Think of it as a self-hosted alternative to platforms like IxDF or Teachable, built with a modern PHP + React stack and designed to be extended.

### Key Features

- **Course Management** — Instructors create courses with modules and lessons (text, video, mixed content) in a drag-and-drop ordered structure
- **Membership Plans & Subscriptions** — Flexible billing tiers (monthly/yearly) with proration, grace periods, and plan switching
- **Progress Tracking** — Real-time completion percentages, resume-where-you-left-off, and automatic recalculation when content changes
- **Certificate Generation** — Auto-generated PDF certificates with unique verification codes upon course completion
- **Discussion Threads** — Per-lesson comment threads with nesting, flagging, and moderation tools
- **Admin Panel** — User management, role assignment, analytics dashboard, revenue reporting, and CSV exports
- **Course Catalog & Search** — Browsable, searchable catalog with category filtering and Redis-powered caching
- **Payment Integration** — Abstracted payment gateway (Stripe/Midtrans) with webhook processing, retry logic, and full audit logging
- **Authentication & Security** — Email verification, account locking, role-based access control, and brute-force protection

## Architecture

GrowthPedia follows a **modular monolith** pattern — a single deployable Laravel application with strict internal module boundaries. Each business domain is encapsulated in its own module under `app/Modules/`.

```
app/
├── Modules/
│   ├── Admin/          # User management, analytics, content moderation
│   ├── Catalog/        # Course browsing, search, category filtering
│   ├── Certificate/    # Certificate generation, verification, PDF export
│   ├── Course/         # Course, module, and lesson CRUD
│   ├── Discussion/     # Comment threads, nesting, flagging
│   ├── Lesson/         # Lesson content delivery
│   ├── Payment/        # Payment gateway abstraction, webhooks, transactions
│   ├── Progress/       # Enrollment tracking, completion calculation
│   ├── Subscription/   # Plan management, billing, grace periods
│   └── User/           # Authentication, authorization, roles
├── Shared/             # Shared DTOs, value objects, base classes
resources/js/
├── Pages/              # Inertia.js page components (React + TypeScript)
├── Components/         # Reusable UI components
├── Hooks/              # Custom React hooks
├── Types/              # TypeScript interfaces mirroring backend DTOs
└── Utils/              # Helpers, formatters, validators
```

Modules communicate through **service interfaces** (contracts) and **domain events** — never through direct model access across boundaries.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.3+, Laravel 13 |
| Frontend | React 19, TypeScript 6, Tailwind CSS 4 |
| Bridge | Inertia.js 3 (server-driven SPA) |
| Database | PostgreSQL |
| Cache & Queue | Redis |
| Build | Vite 8 |
| Testing | Pest PHP 4, Eris (property-based testing) |

## Prerequisites

- PHP 8.3 or higher
- Composer 2.x
- Node.js 20+ and npm
- PostgreSQL 15+
- Redis 7+

## Getting Started

### 1. Clone the repository

```bash
git clone https://github.com/your-org/growthpedia.git
cd growthpedia
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database and Redis credentials:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=growthpedia
DB_USERNAME=your_username
DB_PASSWORD=your_password

CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### 4. Run migrations

```bash
php artisan migrate
```

### 5. Build frontend assets

```bash
npm run build
```

### 6. Start the development server

```bash
composer dev
```

This starts the Laravel server, queue worker, log watcher, and Vite dev server concurrently.

Alternatively, start them individually:

```bash
php artisan serve        # Laravel server
npm run dev              # Vite dev server
php artisan queue:listen # Queue worker
```

Visit [http://localhost:8000](http://localhost:8000) in your browser.

## Testing

GrowthPedia uses [Pest PHP](https://pestphp.com/) for testing and [Eris](https://github.com/giorgiosironi/eris) for property-based testing.

```bash
# Run all tests
php artisan test

# Run with Pest directly
./vendor/bin/pest

# Run a specific module's tests
./vendor/bin/pest app/Modules/Course/Tests/

# Run only property-based tests
./vendor/bin/pest --group=property
```

### Testing Philosophy

The project defines **35 correctness properties** — formal specifications of system behavior that are validated through property-based tests. These properties cover everything from "course creation preserves all input data" to "proration calculation correctness." See the [design document](.kiro/specs/growthpedia-platform/design.md) for the full list.

## Project Structure

```
growthpedia/
├── app/
│   ├── Modules/           # Domain modules (10 modules)
│   │   └── {Module}/
│   │       ├── Controllers/
│   │       ├── Models/
│   │       ├── Services/
│   │       ├── Actions/
│   │       ├── DTOs/
│   │       ├── Events/
│   │       ├── Listeners/
│   │       ├── Requests/
│   │       ├── Policies/
│   │       ├── Exceptions/
│   │       ├── Routes/
│   │       └── Tests/
│   ├── Shared/            # Cross-module shared code
│   └── Http/              # Global middleware
├── resources/
│   ├── js/                # React + TypeScript frontend
│   └── views/             # Blade templates (Inertia root)
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── routes/
├── config/
└── tests/                 # Global test helpers
```

## Module Communication

Modules are decoupled through two mechanisms:

1. **Service Interfaces** — Each module exposes a contract (e.g., `CourseServiceInterface`) registered in the service container. Other modules depend on the interface, not the implementation.

2. **Domain Events** — Side effects across modules are handled via Laravel's event bus:

| Event | Listener |
|-------|----------|
| `LessonCompleted` | Progress module updates tracker |
| `CourseCompleted` | Certificate module generates certificate |
| `PaymentSucceeded` | Subscription module activates subscription |
| `PaymentFailed` | Subscription module starts grace period |
| `CommentFlagged` | Notification sends email to author |
| `LessonRemovedFromCourse` | Progress module recalculates enrollments |

## Contributing

Contributions are welcome! Here's how to get started:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Ensure all tests pass (`php artisan test`)
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

### Guidelines

- Follow the existing modular structure — new features should live in the appropriate module
- Add property-based tests for any new correctness-critical logic
- Use DTOs for data transfer between modules — never pass Eloquent models across module boundaries
- Write Form Request classes for input validation
- Keep controllers thin — business logic belongs in Services or Actions

## Roadmap

- [ ] Course ratings and reviews
- [ ] Instructor earnings dashboard
- [ ] Multi-language support (i18n)
- [ ] Mobile-responsive PWA
- [ ] REST API for third-party integrations
- [ ] SSO / OAuth providers (Google, GitHub)
- [ ] Bulk course import/export
- [ ] Real-time notifications (WebSocket)

## License

This project is open-sourced software licensed under the [MIT License](LICENSE).
