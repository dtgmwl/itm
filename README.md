# IT Service Management System

IT Helpdesk & Task Management built with Laravel 13 + Filament v5.

## Features
- Task Management with status state machine
- Multi-assignee support
- File attachments with signed URLs
- Discussion/comments on tasks
- Calendar view (Guava Calendar)
- Audit trail (immutable task logs)
- Role-based access control (Admin, Head Department, Staff)
- Public service catalog & ticket tracking
- Email + database notifications
- Dashboard with charts & productivity widgets

## Requirements
- PHP 8.3+
- PostgreSQL (recommended) or SQLite
- Composer
- Node.js + NPM

## Setup
```bash
composer setup
```

Or manually:
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
```

## Development
```bash
composer dev
```

## Testing
```bash
composer test
```

## Default Credentials
| Email | Role | Password |
|-------|------|----------|
| admin@it.local | Admin | password |
| head@it.local | Head Department | password |
| staff@it.local | Staff | password |

## License
MIT
