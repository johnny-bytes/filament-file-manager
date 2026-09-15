<?php

namespace BostjanOb\FilamentFileManager\Tests;

use BostjanOb\FilamentFileManager\FilamentFileManagerServiceProvider;
use BostjanOb\FilamentFileManager\Model\FileItem;
use BostjanOb\FilamentFileManager\Pages\FileManager;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Orchestra\Testbench\TestCase;

class FileManagerTest extends TestCase
{
    protected $enablesPackageDiscoveries = true;

    protected function getPackageProviders($app): array
    {
        return [FilamentFileManagerServiceProvider::class, TestPanelProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Storage::fake('files');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_lists_empty_disks_and_refreshes_after_folder_navigation(): void
    {
        $disk = Storage::disk('files');
        self::assertCount(0, FileItem::queryForDiskAndPath($disk)->get());

        $disk->put('folder/child.txt', 'child');
        $disk->put('root.txt', 'root');
        self::assertSame(['folder', 'root.txt'], FileItem::queryForDiskAndPath($disk)->pluck('name')->all());
        $items = FileItem::queryForDiskAndPath($disk, 'folder')->get();
        self::assertSame(['..', 'child.txt'], $items->pluck('name')->all());
        self::assertSame('folder/child.txt', $items->last()->path);
        self::assertTrue($items->last()->delete());
        $disk->assertMissing('folder/child.txt');
        self::assertSame(['..'], FileItem::queryForDiskAndPath($disk, 'folder')->pluck('name')->all());
    }

    public function test_renders_the_page_and_creates_a_folder_with_filament_actions(): void
    {
        Storage::disk('files')->put('hello.txt', 'hello');

        Livewire::test(TestFileManager::class)
            ->assertSuccessful()
            ->assertSee('hello.txt')
            ->callTableAction('create_folder', data: ['name' => 'new-folder'])
            ->assertHasNoTableActionErrors()
            ->assertSee('new-folder');

        self::assertTrue(Storage::disk('files')->directoryExists('new-folder'));
    }
    public function test_uploads_files_to_the_current_folder(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('uploaded.txt', 'uploaded contents');

        Livewire::test(TestFileManager::class)
            ->set('path', 'uploads')
            ->callTableAction('upload_file', data: ['files' => [$file]])
            ->assertHasNoTableActionErrors()
            ->assertSee('uploaded.txt');

        self::assertSame('uploaded contents', Storage::disk('files')->get('uploads/uploaded.txt'));
    }

    public function test_deletes_a_file_through_a_record_action(): void
    {
        $disk = Storage::disk('files');
        $disk->put('delete-me.txt', 'contents');
        $record = FileItem::queryForDiskAndPath($disk)->first();

        Livewire::test(TestFileManager::class)
            ->callTableAction('delete', $record)
            ->assertHasNoTableActionErrors()
            ->assertDontSee('delete-me.txt');

        $disk->assertMissing('delete-me.txt');
    }

    public function test_navigates_into_a_folder_and_back_using_the_name_column(): void
    {
        $disk = Storage::disk('files');
        $disk->put('folder/child.txt', 'child');
        $folder = FileItem::queryForDiskAndPath($disk)->first();

        $page = Livewire::test(TestFileManager::class)
            ->callTableColumnAction('name', $folder)
            ->assertSet('path', 'folder')
            ->assertSee('child.txt');

        $parent = FileItem::queryForDiskAndPath($disk, 'folder')->where('name', '..')->first();
        $page->callTableColumnAction('name', $parent)
            ->assertSet('path', '')
            ->assertDontSee('child.txt');
    }

    public function test_bulk_deletes_selected_files(): void
    {
        $disk = Storage::disk('files');
        $disk->put('first.txt', 'first');
        $disk->put('second.txt', 'second');
        $records = FileItem::queryForDiskAndPath($disk)->get();

        Livewire::test(TestFileManager::class)
            ->callTableBulkAction('delete', $records)
            ->assertHasNoTableBulkActionErrors();

        $disk->assertMissing('first.txt');
        $disk->assertMissing('second.txt');
    }

}

class TestFileManager extends FileManager
{
    public function getDisk(): Filesystem
    {
        return Storage::disk('files');
    }
}

class TestPanelProvider extends \Filament\PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->id('admin')->path('admin')->default()->pages([TestFileManager::class]);
    }
}
