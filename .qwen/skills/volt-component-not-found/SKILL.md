---
name: volt-component-not-found
description: Diagnostic and fix procedure when a Livewire Volt component (⚡ prefix) referenced via <livewire:tag/> is not found — usually a mismatch between Volt mount directory and file location
source: auto-skill
extracted_at: '2026-06-05T12:48:15.543Z'
---

## When to apply
When a user reports that a `⚡`-prefixed Volt component (functional component defined via `new class extends Component` or `use function Livewire\Volt\{...}`) isn't rendering when referenced with `<livewire:component-name />` in a Blade view.

## Diagnostic steps

### 1. Confirm the component is referenced
Search for the `<livewire:component-name />` call in the view files:
```
grep -r 'component-name' resources/views/
```

### 2. Find where Volt is mounted
Check `AppServiceProvider.php` (or `bootstrap/app.php` for Laravel 11+):
```php
Volt::mount(['livewire']); // looks in resources/views/livewire/
// OR
Volt::mount([resource_path('views/components')]); // looks in resources/views/components/
```

The key: Volt **only** scans directories passed to `Volt::mount()` for functional (⚡) components.

### 3. Locate the actual .blade.php file
Look in all likely directories:
```
find resources/views/ -name '*component-name*'
```
The file may have a `⚡` prefix (e.g., `⚡component-name.blade.php`). Volt functional components use this convention.

### 4. Compare directory vs. mount config
- If the file lives in `resources/views/components/` but Volt mounts `['livewire']` — **it won't be found**
- If the file lives in `resources/views/livewire/` but Volt mounts `['components']` — **same issue**

## Fix options

### Option A (Recommended) — Move the file
Move the `⚡`-prefixed file into the directory Volt is already mounted on:
```bash
mv resources/views/components/⚡component-name.blade.php resources/views/livewire/⚡component-name.blade.php
```
This is the cleanest approach: keep the mount config simple and colocate all Volt components.

### Option B — Extend the Volt mount
Add the directory to the mount array in `AppServiceProvider.php`:
```php
Volt::mount([
    resource_path('views/livewire'),
    resource_path('views/components'),
]);
```
Use this if you intentionally want Volt components in multiple directories.

## Key insight
The `⚡` prefix is a Volt naming convention (lightning bolt = "volt"). It signals that the file is a functional Volt component (not a regular Blade partial), but it **does not** control where Livewire searches for it — only `Volt::mount()` does. If the file is in the wrong directory, it's invisible to Livewire regardless of the prefix.