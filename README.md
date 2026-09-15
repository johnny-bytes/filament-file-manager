# Filament File Manager

Version 1.0.0 provides a file manager page for Filament 5 and Laravel 13. Requires PHP 8.3+ and Sushi 2.5.4+.

## Installation

```bash
composer require bostjanob/filament-file-manager:^1.0
```

For the default OneDrive integration, also install `justus/flysystem-onedrive:^1.0` and configure the `onedrive` disk with `driver`, `root`, `directory_type`, `tenant_id`, `client_id`, and `secret`.

## Usage

Extend the page and register it with your Filament panel (or use panel page discovery). The default implementation connects to the configured OneDrive disk. To manage another disk, override `getDisk()`:

```php
namespace App\Filament\Pages;

use BostjanOb\FilamentFileManager\Pages\FileManager;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class PublicFileManager extends FileManager
{
    protected static ?string $navigationLabel = 'Public files';

    public function getDisk(): Filesystem
    {
        return Storage::disk('public');
    }
}
```

Override the public string `$path` property to choose an initial folder.

## Upgrading to 1.0

Upgrade the consuming application to Laravel 13 and Filament 5 first. Page subclasses must use an instance `protected string $view`, and any overridden navigation icon property must use `string|\BackedEnum|null`.

Custom actions now use `Filament\Actions` classes, `schema()` for action forms, and `recordActions()` / `toolbarActions()` on tables. Override `table(Table $table): Table` to customize the table. The available actions are `delete` (record and bulk), `create_folder`, and `upload_file` (header).

If you previously set a disk name in a `$disk` property, use the `getDisk()` override above instead.

## Testing

```bash
composer install
composer test
```

Tests use a fake filesystem and do not require OneDrive credentials.

## License

The MIT License (MIT). See [License File](LICENSE.md).
