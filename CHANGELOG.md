# Changelog

## 1.0.0

- Require PHP 8.3+, Laravel 13, Filament 5, and Sushi 2.5.4+.
- Migrate page property types, actions, and action schemas to Filament 5.
- Resolve the disk lazily and remove registration of a missing Livewire component.
- Save uploads with Filament's per-file callback and refresh the table after uploads.
- Refresh transient listings after navigation and filesystem changes, including empty folders.
- Add tests for rendering, folder creation, uploads, deletion, and refreshed listings.
