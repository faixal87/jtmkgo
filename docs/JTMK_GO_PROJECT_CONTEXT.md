# JTMK Go Project Context

Last updated: 2026-06-30

Use this file as the first handoff document when continuing development on another PC or in a fresh Codex session.

## Project Summary

JTMK Go is a Laravel intranet platform for JTMK operational workflows. It uses Laravel Breeze Blade, Tailwind CSS, MySQL, reusable Blade components, a theme engine, module access control, and modular application structure.

The system should feel like a polished internal SaaS platform, not a traditional CRUD admin panel. Prefer clean split layouts, searchable lists, contextual detail panels, responsive cards, dark-mode readable UI, and no text overflow.

## Development Rules

- Keep Breeze authentication intact.
- Do not delete users.
- Do not touch module access/admin data unless the task is specifically about access control.
- Do not touch Photo Repository data unless the task is about Photo Repository.
- Do not auto-run migrations unless explicitly approved.
- Do not use `migrate:fresh` on production.
- Avoid hard delete for historical academic or workflow data unless a safe delete rule explicitly allows it.
- Prefer soft delete, archive, inactive, disabled, or cancelled states.
- Use eager loading and pagination.
- Avoid DB queries inside Blade loops.
- Preserve existing routes where possible.
- Keep English UI labels unless the task specifically asks for Bahasa Melayu.
- Before AWS deployment, commit/push local stable changes to GitHub first.
- Ask permission before deploying to AWS unless the user explicitly says to push/deploy now.

## Important Local Dirty Files To Exclude

The following changes were cancelled by the user and must not be committed or deployed unless the user explicitly re-approves them:

- `public/favicon.ico`
- `public/favicon.png`
- `go_logo_v1.png`
- `resources/views/auth/login.blade.php`
- `resources/views/welcome.blade.php`

These files may appear as local dirty files. Ignore them for unrelated work.

## Current Main Branch

Active development branch:

```bash
feature/program-go
```

Latest known stable commit after Ganti Go workflow clarification:

```bash
4acd875 Clarify planned Ganti Go early replacement flow
```

## Core Architecture

### Academic Core

Academic Core is the shared data source for academic structures used by Ganti Go, SubjekGo, and future modules.

Main tables:

- `academic_semesters`
- `academic_subjects`
- `academic_class_groups`
- `academic_subject_offerings`
- `academic_subject_offering_class_groups`

Key rules:

- Academic subjects are master data.
- Subject offerings belong to academic semesters.
- Class groups are tied to academic session/semester/programme/cohort context.
- Class groups are historical records and should not be overwritten when a new semester starts.
- Generate new class group records for new semesters.
- No academic advisor dependency is currently required for SubjekGo subject preference workflow.

### Access Control

The system uses module access and module admin assignments.

Important UX rules:

- Super admin manages system/platform.
- Module admins manage their own modules.
- Access Control should use global server-side search, not current-page DOM filtering.
- Preserve selected user, active tab, search/filter state, and scroll position.
- Do not display IC number in compact user lists.

There is also a feature permission system for KJ/KPRO sensitive staff directory access:

- Permission key: `staff-directory-sensitive-view`
- Allows full IC and audit requirement link visibility in Staff Directory.

### Theme And Branding

Themes:

- `default`
- `blue`
- `dark`
- `purple-matcha`

Theme preference is per user where possible. Sidebar and workspace must be theme-aware and visually separated.

Branding supports separated logo settings:

- Landing page logos
- Sidebar logo
- Logo size
- Footer/system title/version

Pending approval page should use branding settings, not old hardcoded logo/version.

## Modules

### Ganti Go

Purpose: class replacement planning, implementation, verification, monitoring, and analytics.

Status flow:

- `planned`
- `pending_verification`
- `verified`
- `rejected`
- `cancelled`
- `overdue`

Workflow:

- Planned Replacement:
  - Used when replacement class has not yet been conducted.
  - Replacement may be scheduled before or after the original class date.
  - Replacement date must not have already passed at creation time.
  - Lecturer marks it as implemented after the replacement class is conducted.
  - Then status becomes `pending_verification`.
- Already Implemented Replacement:
  - Used when replacement class has already been conducted.
  - Replacement date must be today or in the past.
  - Status becomes `pending_verification` immediately.

Verification:

- Only another Ganti Go module admin can verify/reject.
- Self-verification is not allowed.
- Super admin has analytics/dashboard visibility only, not lecturer workflow.

Academic Core:

- Create/edit replacement forms should use Academic Core current semester offerings and class groups.
- Keep legacy records readable with fallback text where needed.

### SubjekGo

Purpose: lecturer subject preference management.

Normal lecturer menus:

- Dashboard
- Subject Preference
- My Selections
- My Teaching Experience

Admin section is separate and collapsible under SubjekGo:

- Preference Review
- Sessions
- Academic Subjects
- Class Groups
- Subject Offerings
- Analytics

Rules:

- Lecturer selects exactly 4 preferred subjects.
- No duplicate subject selection.
- Only open session allows submission/edit.
- Session links to Academic Core semester.
- Offered subjects come from Academic Core subject offerings.
- Teaching history is self-declared by the lecturer, not automatically generated from selected preferences.
- Course coordinator display should use profile photo thumbnail when available and must not overflow.

Preference Review UX:

- Starts with two main cards:
  - By Lecturer
  - By Subject
- By Lecturer:
  - Left: lecturer list.
  - Right: selected lecturer choices in compact table/list.
  - Choice 1 and 2 highlighted.
- By Subject:
  - Left: subject list.
  - Right: choice accordions with lecturers and experience.
  - Preserve query string and pagination state.

### Photo Repository

Purpose: official portrait/photo repository for staff, VIP, management, and external profiles.

Normal menus:

- Dashboard
- Gallery
- My Photos
- Upload Photo

Admin section:

- Analytics
- Review Queue
- Profiles
- Categories

Rules:

- Users can upload their own photos.
- Admin can upload for existing or external profiles.
- Approval workflow exists.
- Users can delete their own photos.
- Module admin can delete/archive photos.
- Gallery should show approved photos. Featured photos are promoted/highlighted, but searchable approved photos should remain discoverable unless intentionally hidden by status/filter.
- If a user has no uploaded profile photo, system may use the current official Photo Repository photo as fallback.

Download:

- Store optimized WEBP internally.
- Default download JPG via dynamic conversion.
- WEBP download also supported.

### ProgramGo

Purpose: programme/activity/course paperwork, implementation report links, verification workflow, and budget monitoring.

Normal menus:

- Dashboard
- Submit Activity
- Activities

Admin section:

- Review Submissions
- Budget Monitoring

Important terminology:

- "Speaker" should be displayed as "Trainer".
- "Implementation Report Link" should be displayed as "Programme Reports".
- "Approved Budget" should be displayed as "Budget Usage".

Workflow statuses:

- `draft`
- `in_progress`
- `completed`
- `pending_verification`
- `approved`
- `returned_for_correction`
- `rejected`

Budget rule:

- Only `approved` activities count toward Budget Usage analytics.
- Non-approved completed or pending items are Pending Budget Verification.

Activities UX:

- Main Activities page starts with two cards:
  - My Activities
  - Other Activities
- My Activities shows own submissions.
- Other Activities shows approved activities from other users only.

Co-author feature:

- Activities may have collaborators/co-authors.
- Co-authors can help edit or submit depending on implemented permission rules.
- This supports real cases where paperwork and reports are handled by different staff.

### LinkGo

Purpose: central link repository to prevent important links being lost in chat apps.

No approval workflow:

- Submitted links publish immediately.
- Users manage own links.
- Module admin manages all links and portfolios.

Normal menus:

- Dashboard
- Link Library
- Submit Link
- My Links

Admin section:

- Manage Links
- Portfolios
- Analytics

UX rules:

- Link Library should show compact title list only.
- Clicking title opens compact detail page/card.
- Dashboard Recently Added should show title, added date, and owner name only. Pinned Links remain as existing card style.
- Portfolio admin should use compact list, not large cards.
- Slug is internal URL/key generation; if not useful to user, hide it from normal form display and auto-generate from name.

QR:

- QR code generation exists for links.

Copy:

- Copy action may succeed even if tracking endpoint fails. UI should not falsely show copy failed when clipboard copy already succeeded.

### Survey

Originally SurveyGo, now should be labelled simply "Survey" because it is a small system function, not a main module.

Purpose: baseline and impact assessment for JTMK Go usage.

Rules:

- Super admin controls surveys.
- Baseline can be forced or optional.
- Impact is not forced.
- User must complete baseline before answering impact, so before/after comparison is meaningful.
- Users should see sidebar status for surveys they have completed or still need to complete.
- If no survey is available, show a friendly empty state.
- Notifications should be created when users need to answer a survey.

Question UX:

- Survey form should be page-by-page, not one very long page.
- 4 pages is acceptable.
- Show progress percentage or question count.

Language:

- User-facing baseline/impact questions can be Bahasa Melayu if requested.
- Avoid awkward phrase "setanding dengan jabatan lain"; use more polished wording around digital transformation and professional department improvement.

Analytics:

- Each survey should have individual result.
- Categories:
  - Before
  - After
  - Before + After comparison
- Include colorful charts.
- Provide summary whether the system impact is positive based on before/after calculation.
- Super admin can delete responses if a user submitted incorrectly.

### Staff Directory

Purpose: global staff lookup for all authenticated approved users.

Rules:

- Exclude super admin account from directory results.
- Compact left list shows avatar/initial and full name only.
- Detail panel shows profile information.
- IC is masked for normal users.
- Full IC and audit requirement link are visible only to super admin or users with `staff-directory-sensitive-view`.
- Show age below date of birth, calculated from date of birth.
- Directory is view-only.

Search:

- Server-side global search by name, email, phone, department, grade, staff short code, and IC where applicable.
- Do not search only the current page.

### Notifications

Notifications should alert super admin about:

- New user registrations pending approval.
- Module access requests.
- Other user requests where applicable.
- Birthday notifications.
- Survey notifications.

Notifications should link directly to the relevant action page where possible.

## UI Standards

- Use responsive layouts.
- Avoid fixed-width cards that break on resize.
- Use `min-w-0`, `break-words`, `truncate`, or wrapping where needed.
- Avoid IC numbers in compact lists.
- Use split list/detail layout where useful.
- Use compact cards for details, not oversized cards.
- Use AJAX list updates where it improves UX, especially Staff Directory and review workspaces.
- Preserve scroll position when opening sidebar sections and action dropdowns.
- Avoid `href="#"` for dropdown triggers; use `button type="button"`.

## Performance Standards

- Use eager loading for relationships.
- Use aggregation queries for dashboard metrics.
- Paginate large lists.
- Search server-side before pagination.
- Cache static settings/modules where safe.
- Do not cache raw `Illuminate\Support\Collection` objects directly; convert to arrays if caching.
- No DB query inside Blade loops.

## Deployment Policy

- Commit stable changes first.
- Push to GitHub.
- Ask permission before AWS deploy unless user explicitly requests deploy now.
- Do not push cancelled local logo/favicon changes.
- Do not run migrations automatically unless requested/approved.
- If migrations are required, list manual commands.

## How To Use This Context On A New PC

After cloning the repo, tell Codex:

```text
Read docs/JTMK_GO_PROJECT_CONTEXT.md and docs/JTMK_GO_DEPLOYMENT_RUNBOOK.md before making changes.
Follow the deployment policy and do not commit cancelled logo/favicon files.
```

