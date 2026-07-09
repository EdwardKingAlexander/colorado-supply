<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BackupConfigurationTest extends TestCase
{
    public function test_backup_destination_resolves_to_the_local_disk_pending_offsite_credentials(): void
    {
        $this->assertSame(['local'], config('backup.backup.destination.disks'));
    }

    public function test_backup_source_includes_non_regeneratable_storage_and_excludes_temp_paths(): void
    {
        $include = config('backup.backup.source.files.include');
        $exclude = config('backup.backup.source.files.exclude');

        $this->assertContains(storage_path('app'), $include);
        $this->assertContains(storage_path('app/backup-temp'), $exclude);
        $this->assertContains(storage_path('app/private/livewire-tmp'), $exclude);
    }

    public function test_backup_database_connection_is_configured(): void
    {
        $this->assertContains(
            config('database.default'),
            config('backup.backup.source.databases')
        );
    }

    public function test_scheduled_backup_commands_are_registered(): void
    {
        $events = Artisan::call('schedule:list');

        $output = Artisan::output();

        $this->assertStringContainsString('backup:run', $output);
        $this->assertStringContainsString('backup:clean', $output);
        $this->assertStringContainsString('backup:monitor', $output);
    }
}
