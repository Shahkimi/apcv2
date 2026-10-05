# ApcQueue

Ceremony attendance and calling-order management system — verify attendance at the door, manage
calling order and seating, and drive a live presentation screen for large staff ceremonies.

<p align="left">
  <img src="https://img.shields.io/badge/PHP-%5E8.3-777bb4" alt="PHP ^8.3">
  <img src="https://img.shields.io/badge/Laravel-13-ff2d20" alt="Laravel 13">
  <img src="https://img.shields.io/badge/Tests-Pest%204-2fb475" alt="Pest 4">
  <img src="https://img.shields.io/badge/License-Internal%20use-lightgrey" alt="License: internal use">
</p>

## Overview

ApcQueue runs the operational side of a staff recognition ceremony — currently configured for two
event modes:

- **Anugerah Pekerja Cemerlang (APC)** — excellent-staff award ceremony, calling order driven by
  seat number / table.
- **Jasamu Dikenang** — a service-recognition/retirement ceremony, which additionally tracks
  retirement date, retirement type, and years of service per officer.

Three roles use the system: **Admin** (event setup, master data, verification, reports),
**Media** (runs the presentation screen and monitors live progress), and **User** (read-only view
of their own attendance).

End-to-end flow: officers are bulk-imported → an admin opens a session ("sesi majlis") and marks it
active → attendance is verified at the door → officers are called in order (by seat number, or by
arrival order once a session is switched to "late" mode) → the media operator announces each
officer, which is tracked and pushed to a big-screen presentation view with a configurable
backdrop and font/position styling → progress is visible live on an analytics dashboard → an admin
can generate a PDF/on-screen attendance report afterward, and reset the whole event for a rerun.

## Product Requirements (PRD)

### Problem statement
Manually calling names off a printed list at a large ceremony (hundreds to thousands of attendees)
is slow, error-prone, and gives organizers no live visibility into progress, no way to reorder for
late arrivals without confusion, and no auditable record of who was actually announced and when.

### Goals
- Verify attendance quickly at the door, searchable by name/KP number.
- Compute a deterministic calling order (seat/table-based, with a separate late-arrival queue) so
  the person on stage and the person calling names never disagree on "who's next."
- Drive a presentation screen that always reflects the current officer, with zero double-calls or
  flicker between updates.
- Give organizers a live, low-overhead view of how far through the list the ceremony is.
- Support two visually and structurally different ceremony types (APC vs. Jasamu Dikenang) without
  forking the codebase.
- Let an admin import a full officer roster from CSV/XLSX and reset the entire event for reuse.

### Non-goals
- Payment, ticketing, or public self-service RSVP (RSVP status is imported, not collected in-app).
- Multi-tenant / multi-organization support — one event configuration at a time.
- Real-time video/broadcast integration — the presentation screen is a styled Blade view, not a
  broadcast graphics package.

### Personas
| Persona | Role | Needs |
|---|---|---|
| Event admin | `admin` | Configure sessions/master data, verify attendance, run reports, import data, reset the system between events |
| Media operator | `media` | Drive the presentation screen, control display styling, announce officers, watch live progress |
| Attendee-facing viewer | `user` | Check their own attendance/session status only |

### Functional requirements (by role)

**Admin — Kawalan (control panel)**
- Session ("Sesi Majlis") CRUD: name, active/late flags, countdown, seat offset, morning/afternoon
  slot; only one session is "on-air" at a time; switching a late session back to inactive
  auto-assigns late calling numbers to everyone already marked present (`SesiMajlisObserver`).
- Event mode switch (APC ⇄ Jasamu Dikenang) — changes which officer fields are required/shown
  system-wide.
- Master data CRUD: PTJ (department), Jawatan (position), Gred (grade), Meja (seating capacity),
  Bersara (retirement type, Jasamu mode).
- Presentation backdrop image management (upload, activate, reorder).
- User account management (create/edit/delete admin, media, and user accounts).
- Bulk officer import from CSV/TXT/XLSX with column mapping, validation preview, and chunked
  transactional commit.
- Full system reset — clears all attendance, session assignment, table/late numbers, and
  announcement history in one transaction, for reusing the system on a new event.
- Attendance verification (shared with `user`/`media` via `AbstractKehadiranController`):
  search, view officer detail, confirm/cancel attendance.
- Attendance report generation with on-time / late / not-attended breakdown, live preview table,
  and PDF export.
- Live announcement analytics (progress %, current position, history of announced officers).

**Media**
- Presentation ("Senarai") screen: full-screen officer slide (name, position, department, and —
  in Jasamu mode — retirement date/type/tenure), current backdrop, and live style config.
- Presentation style profiles: save/apply up to 10 named font-size/position presets.
- Progress control: advance/set the "currently announcing" pointer; each announcement is recorded
  once (idempotent) for history and analytics.
- Live analytics dashboard: progress percentage, current index, and a searchable table of
  already-announced officers with relative timestamps — updated via low-overhead smart polling
  rather than constant full-page refresh.
- Attendance display board ("Paparan"): read-only live list of attended officers.

**User**
- View their own attendance status for the active session.

### Non-functional requirements
- **Concurrency safety**: late-arrival calling numbers are assigned under row locks to prevent
  duplicate numbers when multiple counters confirm attendance simultaneously
  (`KehadiranCallingService`).
- **No double-announce / no UI flicker**: the presentation screen's navigation state is
  sequence-numbered and freshness-checked against the server so a stale poll response can never
  revert an operator's manual navigation.
- **Efficient polling**: analytics screens poll cheap counters first and only reload the full
  DataTable when something actually changed, instead of blind fixed-interval refreshing.
- **Localized UI**: interface strings are in Bahasa Melayu throughout.
- **Deterministic ordering**: calling order always has a tie-breaker (`id`) so identical queries
  never reorder officers between page loads.

### Data model summary
| Entity | Purpose |
|---|---|
| `Pegawai` | An officer/attendee — identity, RSVP, seat/table/late-call numbers, attendance flag, and (Jasamu mode) retirement info |
| `SesiMajlis` | A ceremony session/sitting — active/late state, seat offset, morning/afternoon slot |
| `Ptj` / `Jawatan` / `Gred` | Department / position / grade lookup tables |
| `Meja` | Seating capacity configuration used to derive table number from seat number |
| `Bersara` | Retirement-type lookup (Jasamu Dikenang mode) |
| `AnnouncedOfficer` | Record of each officer announced on the presentation screen, scoped per session |
| `SenaraiProgress` | The "currently announcing" pointer/progress per session |
| `Backdrop` | Uploaded presentation-screen background images |
| `PresentationProfile` | Saved presentation font-size/position style presets |
| `SystemSetting` | Generic typed key/value application settings store |

## Tech stack

| Layer | Technology |
|---|---|
| Backend | PHP ^8.3, Laravel 13 |
| Auth | Laravel Breeze (username-based, no email) |
| Tables | Yajra DataTables (server-side processing) |
| Reports | barryvdh/laravel-dompdf, PhpSpreadsheet (XLSX import) |
| Frontend | Alpine.js, Tailwind CSS 3, Vite |
| UI feedback | SweetAlert2 |
| Charts | Chart.js, ApexCharts |
| Icons | Remixicon |
| Testing | Pest 4 (`tests/Feature`, `tests/Unit`) |

No Docker/Sail setup is included — the project targets a standard local PHP environment
(e.g. Laravel Herd or Valet).

## Getting started

### Prerequisites
- PHP 8.3+
- Composer
- Node.js + npm
- MySQL, or SQLite (the bundled `.env.example` default)

### Quick setup
```bash
composer setup
```
This installs PHP dependencies, copies `.env`, generates the app key, runs migrations, links
storage, installs npm dependencies, and builds frontend assets.

### Manual setup
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
npm install
npm run build
```

By default `.env.example` uses `DB_CONNECTION=sqlite`. To use MySQL instead, set
`DB_CONNECTION=mysql` along with `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
before running migrations.

### Local development
```bash
composer dev
```
Runs the PHP dev server, the queue listener, and the Vite dev server together.

## Testing

```bash
php artisan test
```

Coverage includes standard auth flows (login, registration, password reset/confirm, email
verification) plus feature tests for event-mode switching, Kawalan master-data CRUD, system reset,
late-arrival calling-number assignment, presentation profile CRUD, Jasamu-mode import/display, and
announced-officer progress tracking.

## Project structure

```
app/Http/Controllers/
  Admin/        Admin-only controllers (Kawalan sub-namespace = control panel / master data)
  Media/        Media-role controllers (presentation, progress, display settings)
  User/         User-role controllers
  Kehadiran/    Shared attendance controller/traits reused by admin & user roles

app/Services/
  Kehadiran/    Calling order, table-number math, late-numbering, announcement progress
  Presentation/ Presentation screen style config
  Kawalan/      System reset
  EventModeService.php, SettingsService.php, DatabaseImportService.php

resources/
  admin/, media/, user/   Role-specific Blade views (registered view namespaces)
  views/components/       Shared UI components (data tables, layouts, dashboard shell)
```

## Roles & access

| Role | Middleware | Access |
|---|---|---|
| `admin` | `role.admin` | Full control panel, master data, reports, verification, reset |
| `media` | `role.media` | Presentation screen, display styling, live progress/analytics |
| `user` | `role.user` | Own attendance status only |

## License

Internal / proprietary use.
