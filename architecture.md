# IT Service Management - Architecture

## Overview
Laravel 13 + Filament v5 based IT Helpdesk & Task Management System with role-based access control.

## Tech Stack
- **Backend**: PHP 8.3, Laravel 13, Filament v5
- **Frontend**: Tailwind CSS v4, Alpine.js, Livewire (Volt + Flux)
- **Database**: PostgreSQL (production), SQLite (testing)
- **Queue**: Database driver
- **Auth**: Spatie Laravel Permission (RBAC)

## Roles & Permissions
| Role | Capabilities |
|------|------------|
| `admin` | Full access (manage users, departments, tasks) |
| `head_department` | Manage department tasks, reports, assignments |
| `staff` | Update own task status, comment, upload attachments |

## Task Status State Machine
```
Open → Assigned → In Progress → Pending → Completed
  ↓       ↓            ↓           ↓
  └── Cancelled ←──────┴───────────┘
```

## Key Architecture Decisions

### Events & Listeners
- **Synchronous** (`LogTaskActivity`): Audit logging must be immediate
- **Queued** (`notifications` queue): Email/DB notifications can tolerate delay

### File Storage
- All attachments stored on `private` disk
- Downloads via signed temporary URLs (15 min expiry)
- UUID-based filenames prevent enumeration

### Data Scoping
- `getEloquentQuery()` override in TaskResource enforces row-level security
- Admin sees all, HOD sees department tasks, staff sees assigned + public tasks

## Directory Structure
```
app/
├── DTOs/           # Data Transfer Objects (CreateTaskDTO)
├── Enums/          # TaskStatus, TaskPriority, TaskSource, ActionType
├── Events/         # Domain events (TaskCreated, TaskAssigned, etc.)
├── Exceptions/     # InvalidTaskTransitionException
├── Filament/       # Admin panel (Resources, Pages, Widgets, RelationManagers)
├── Http/           # Controllers, Responses
├── Listeners/      # Event handlers (LogTaskActivity, NotifyAssignedStaff, etc.)
├── Livewire/       # Full Livewire component (TaskDiscussion)
├── Models/         # Eloquent models
├── Notifications/  # Mail + Database notifications
├── Policies/       # Authorization policies
├── Providers/      # Service providers
├── Repositories/   # Complex reporting queries
└── Services/       # Business logic (TaskService, FileUploadService, TaskAssignmentService)
```
