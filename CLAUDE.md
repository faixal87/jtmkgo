# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

JTMK Go is a Laravel intranet portal for JTMK POLIMAS staff — a centralized, single-sign-on access point for departmental modules (class replacement scheduling, staff photo repository, programme/activity paperwork, link repository, subject preference management, surveys). Stack: Laravel 13 + Breeze (Blade), Tailwind CSS, MySQL, Vite, Alpine.js, ApexCharts. Local dev runs on Laravel Herd.

Read `docs/JTMK_GO_PROJECT_CONTEXT.md` before making non-trivial changes — it is the canonical, actively-maintained spec of module behavior, workflow rules, and UX conventions and takes precedence over inference from code alone. Read `docs/JTMK_GO_DEPLOYMENT_RUNBOOK.md` before any deployment task.

Current active branch: `feature/program-go`.

## Commands

```bash
# Full dev environment (server + queue listener + log tail + vite), one command:
composer dev

# Individually:
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
php artisan pail --timeout=0        # live log tail
npm run dev                         # vite

# Build frontend for production
npm run build

# Tests (PHPUnit, not Pest)
composer test                       # clears config cache, then runs php artisan test
php artisan test                                          # all tests
php artisan test --filter=RegistrationTest                # one test class
php artisan test tests/Feature/Auth/RegistrationTest.php   # one file
php artisan test --filter=test_method_name                 # one test method

# Lint / format (Laravel Pint)
vendor/bin/pint            # fix
vendor/bin/pint --test     # check only, no changes

# Migrations (see Development Rules below — do not run without approval)
php artisan migrate
php artisan migrate:status
```

Testing DB is SQLite in-memory (`phpunit.xml`), not the MySQL dev database — safe to run freely without touching real data.

## Critical Development Rules

These come from `docs/JTMK_GO_PROJECT_CONTEXT.md` and are load-bearing — violating them breaks real workflows or destroys data:

- Keep Breeze authentication intact.
- Do not delete users. Do not touch module access/admin data unless the task is specifically about access control. Do not touch Photo Repository data unless the task is about Photo Repository.
- Do not run `php artisan migrate` (or especially `migrate:fresh`) without explicit approval — never on production.
- Avoid hard delete for historical academic/workflow data; prefer soft delete, archive, inactive, disabled, or cancelled states.
- Use eager loading and pagination; never run DB queries inside Blade loops; search server-side, not DOM/current-page filtering.
- Preserve existing routes where possible.
- Keep UI labels in English unless a task specifically asks for Bahasa Melayu.
- Before AWS deployment: commit/push to GitHub first, then ask permission before deploying unless the user explicitly says to deploy now.
- These files were intentionally reverted/cancelled by the user — do not commit or deploy changes to them unless explicitly re-approved: `public/favicon.ico`, `public/favicon.png`, `go_logo_v1.png`, `resources/views/auth/login.blade.php`, `resources/views/welcome.blade.php`. They may show as locally dirty; ignore for unrelated work.

## Architecture

### Modular structure

Each business module lives under `app/Modules/<ModuleName>/` with its own `Controllers/`, `Models/`, `Policies/`, `Requests/`, `Services/` — a self-contained slice rather than the framework-default flat `app/Http`, `app/Models`. Current modules: `AcademicCore`, `GantiGo`, `LinkGo`, `PhotoRepository`, `ProgramGo`, `SubjekGo`, `SurveyGo`. Platform-level (non-module) concerns — auth, users, module/access-control admin, profile, notifications — live in the conventional `app/Http/Controllers` (with `Auth/`, `ModuleAdmin/`, `SuperAdmin/` subfolders) and `app/Models`.

Business logic that spans more than simple CRUD belongs in a module's `Services/` class (e.g. `GantiGo\Services\ClassReplacementWorkflowService` drives the replacement status state machine and notifications), not in controllers.

Views mirror this: `resources/views/<module-slug>/` (e.g. `resources/views/ganti-go/`), with an `admin/` subfolder for module-admin-only screens.

### Academic Core as shared data source

`AcademicCore` (`academic_semesters`, `academic_subjects`, `academic_class_groups`, `academic_subject_offerings`, `academic_subject_offering_class_groups`) is the canonical academic data model shared by `GantiGo` and `SubjekGo`. Subjects are master data; offerings belong to semesters; class groups are historical snapshots tied to a session/semester/programme/cohort and must never be overwritten for a new semester — generate new records instead. `GantiGo` retains its own legacy `Course`/`ClassGroup`/`Semester` models (bridged to Academic Core via migration) for backward compatibility with older records.

### Routing and access control layering (`routes/web.php`)

Every module route group is wrapped in ordered middleware, from outermost to innermost:

```
auth → session.timeout → verified → approved → module.access:<slug> → [module.admin:<slug> for admin-only routes] → [can:manage-academic-core for academic data]
```

- `approved` (`EnsureUserApproved`) — blocks users whose `account_status` isn't `approved` (self-registration starts `pending`).
- `module.access:<slug>` (`EnsureModuleAccess`) — per-user module grant, checked via `Module`/`ModuleUserAccess` pivot.
- `module.admin:<slug>` (`EnsureModuleAdmin`) — module-admin-only subtree, nested inside the module group.
- `super.admin` (`EnsureSuperAdmin`) — platform-wide admin routes (`/super-admin/*`): user approval, module management, branding, global access control. Super admins have platform oversight but not lecturer-level workflow access inside modules (e.g. Ganti Go: analytics/dashboard visibility only).
- `session.timeout` (`HandleSessionTimeout`) enforces idle logout; `SetLocale` and `EnsureForcedBaselineSurveyCompleted` are global `web` middleware appended in `bootstrap/app.php` (the latter can redirect any authenticated request until the user completes a forced baseline survey).

Middleware aliases are registered in `bootstrap/app.php`, not `app/Http/Kernel.php` (Laravel 13's bootstrap-based middleware config).

Per-module `view-<slug>`/`manage-<slug>` gates (e.g. `manage-academic-core`) are registered in `AppServiceProvider::boot()`, each delegating to that module's `Policies/` class — this is what routes like `can:manage-academic-core` check.

### Access control model

Three-tier: **Super Admin** (full platform control) → **Module Admin** (per-module management, via `ModuleAdmin` pivot) → **Staff User** (assigned module access only, via `ModuleUserAccess`). A separate `FeaturePermission` system grants narrower cross-cutting permissions independent of module admin status (e.g. `staff-directory-sensitive-view` for full IC-number visibility in Staff Directory). New users self-register with an IC number and stay `pending` until a super admin approves them.

### Dashboard module caching

The `/dashboard` route caches per-user module visibility via `SafeArrayCache` (`app/Support`) for 30s — a wrapper that avoids caching raw `Illuminate\Support\Collection` objects directly (arrays only), per the caching rule in the project context doc. Follow this pattern for any new cached query result.

### Cross-module shared systems

- **Notifications** (`app/Models/Notification.php`, `NotificationCenterController`, `NotificationComposerController`) — in-app notification feed alerting super admins about pending approvals, module access requests, and other events; not tied to any one module.
- **Email / announcements** (`app/Mail`, `App\Jobs\SendAnnouncementEmail`/`SendNotificationEmail`, `App\Models\EmailLog`, `App\Support\MailSettings`) — separate from the in-app notification feed above; this is actual outbound mail (announcements, birthday greetings, password reset). Mail provider (SMTP or SES) is DB-configurable at runtime via `SuperAdmin\MailSettingsController`, not `.env` — `MailSettings::applyToRuntimeConfig()` must be called immediately before any `Mail::to()->send()`, since Laravel resolves the mailer/transport from config on first use (calling it inside a Mailable's `build()` is too late). Every send is a queued job tracked in `EmailLog` (queued/sent/failed), so the queue listener must be running for mail to actually go out; `SuperAdmin\AnnouncementController` throttles bulk sends to 30 emails/minute via per-recipient job delay.
- **Staff Directory** — global read-only staff lookup across all modules, with server-side search and IC-number masking gated by `staff-directory-sensitive-view`.
- **Theming** — per-user theme preference (`default`, `blue`, `dark`, `purple-matcha`) stored on `User`, applied via layout partials in `resources/views/layouts/`.

### Scheduled commands

Custom Artisan commands and their schedule live in `routes/console.php` (Laravel 13 has no `app/Console/Kernel.php`), not a module: `notifications:birthday` (daily 08:00), `ganti-go:remind-implementation` (daily 08:15), `ganti-go:mark-overdue` (daily 00:10). `academic-core:doctor` is an on-demand diagnostic (`php artisan academic-core:doctor`) reporting Academic Core migration health — counts, legacy-table status, and orphan checks across `GantiGo`/`SubjekGo` — reach for it first when debugging Academic Core data-integrity issues.

### Frontend

Blade + Tailwind, no SPA framework. Alpine.js for interactivity, ApexCharts for analytics/dashboard charts. Vite bundles assets from `resources/`; `laravel-vite-plugin` wires it into Blade via `@vite`. Tailwind config is v3-style (`tailwind.config.js`) despite `@tailwindcss/vite` v4 plugin being present in `package.json`.
