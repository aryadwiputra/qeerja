<div align="center">
    <br/>
    <h1>Qeerja</h1>
    <p><em>Project work, clearly connected.</em></p>
    <p>
        <a href="#">
            <img src="https://img.shields.io/badge/PHP-8.3%20|%208.4%20|%208.5-777BB4?logo=php&logoColor=white" alt="PHP">
        </a>
        <a href="#">
            <img src="https://img.shields.io/badge/Laravel-13-F9324C?logo=laravel&logoColor=white" alt="Laravel">
        </a>
        <a href="#">
            <img src="https://img.shields.io/badge/React-19-58C4DC?logo=react&logoColor=white" alt="React">
        </a>
        <a href="#">
            <img src="https://img.shields.io/badge/Inertia-3-7B3FE4?logo=inertia&logoColor=white" alt="Inertia">
        </a>
        <a href="#">
            <img src="https://img.shields.io/badge/license-MIT-blue" alt="License">
        </a>
    </p>
</div>

---

**Qeerja** is a modern open-source project management tool built on Laravel, Inertia, and React. It brings boards, sprints, approvals, automation, and releases into one focused workspace — with real-time collaboration via Laravel Reverb.

## Features

- **Kanban Board** — Drag-and-drop with real-time sync, swimlanes, WIP limits, multiple boards.
- **Sprints** — Time-boxed iterations with burndown charts, velocity tracking, and backlog management.
- **Approval Flows** — Gate column transitions with configurable approvers and minimum approvals.
- **Automation Rules** — Trigger-condition-action engine for status changes, assignments, labels, and notifications.
- **SLA Policies** — Response and resolution time targets per task type.
- **Releases** — Group completed work into releases with progress tracking.
- **Goals & Key Results** — Define objectives and track linked epics.
- **Cross-Project Views** — Timeline (Gantt) and board spanning multiple projects.
- **Reports** — Velocity charts, burndown tracking, workload distribution.
- **Real-time Collaboration** — Live board updates, typing indicators, activity feeds via Laravel Reverb.
- **Role-Based Access** — Permission system with workspace and project-level roles.
- **GitHub Integration** — Link commits and PRs to tasks.
- **Two-Factor Authentication** — TOTP and passkeys (WebAuthn).
- **i18n** — English and Indonesian (Bahasa Indonesia) language support.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3+, Laravel 13 |
| Frontend | React 19, Inertia 3 |
| Styling | Tailwind CSS 4, shadcn/ui |
| Realtime | Laravel Reverb, Laravel Echo |
| Database | SQLite (dev), MySQL (production) |
| Queue | Database driver |
| Auth | Laravel Fortify (register, login, 2FA, passkeys) |
| Testing | Pest 4 |
| Tooling | Laravel Pint, ESLint, Prettier, TypeScript strict |

## Requirements

- PHP 8.3+
- Composer 2
- Node.js 20+
- NPM 10+

## Quick Start

```bash
# Clone the repository
git clone git@github.com:aryadwiputra/qeerja.git
cd qeerja

# Install dependencies
composer install
npm install

# Set up environment
cp .env.example .env
php artisan key:generate
php artisan storage:link

# Run migrations
php artisan migrate

# Build frontend
npm run build

# Start development
composer run dev
```

Open `http://localhost:8000` in your browser.

## Development

```bash
# Full dev environment (server + queue + logs + reverb + vite)
composer run dev

# Run all checks (lint → format → types → tests)
composer run ci:check

# Run tests
composer test

# PHP formatting
vendor/bin/pint --dirty

# Frontend checks
npm run lint
npm run format
npm run types:check
```

## Testing

Qeerja uses [Pest 4](https://pestphp.com/) for testing.

```bash
# Run all tests
php artisan test

# Run a specific test
php artisan test --compact --filter=testName

# Run tests without lint check (CI uses this)
./vendor/bin/pest
```

## Security

If you discover a security vulnerability, please email the maintainer directly. See [SECURITY.md](SECURITY.md) for details.

## License

Qeerja is open-source software licensed under the [MIT license](LICENSE.md).

---

<p align="center">Built with Laravel, Inertia, React, and Tailwind.</p>
