<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\CategoryResource\Pages\ImportCategories;
use App\Models\Category;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CategoryImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');

        $user = User::factory()->create();
        $role = Role::query()->firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web'],
            ['name' => 'super_admin', 'guard_name' => 'web'],
        );
        $user->assignRole($role);

        $this->actingAs($user);
    }

    private function writeCsv(string $contents): string
    {
        $path = sys_get_temp_dir().'/category_import_'.uniqid().'.csv';
        file_put_contents($path, $contents);

        return $path;
    }

    public function test_admin_can_open_the_import_page(): void
    {
        Livewire::test(ImportCategories::class)->assertOk();
    }

    public function test_preview_is_a_dry_run_and_creates_nothing(): void
    {
        $path = $this->writeCsv(<<<'CSV'
        name,parent,description
        Abrasives,,Top level
        Sanding,Abrasives,
        Belts,Sanding,
        Discs,Sanding,
        CSV);

        Livewire::test(ImportCategories::class)
            ->set('uploadedAbsolutePath', $path)
            ->call('analyze')
            ->assertSet('preview.newCount', 4)
            ->assertSet('preview.errorCount', 0);

        $this->assertSame(0, Category::count());

        @unlink($path);
    }

    public function test_confirm_creates_the_nested_categories(): void
    {
        $path = $this->writeCsv(<<<'CSV'
        name,parent,description
        Abrasives,,Top level
        Sanding,Abrasives,
        Belts,Sanding,
        Discs,Sanding,
        CSV);

        Livewire::test(ImportCategories::class)
            ->set('uploadedAbsolutePath', $path)
            ->call('analyze')
            ->call('confirm');

        $this->assertSame(4, Category::count());

        $sanding = Category::where('name', 'Sanding')->firstOrFail();
        $belts = Category::where('name', 'Belts')->firstOrFail();
        $this->assertSame($sanding->id, $belts->parent_id);

        @unlink($path);
    }

    public function test_rows_with_errors_block_the_import(): void
    {
        $path = $this->writeCsv(<<<'CSV'
        name,parent,description
        Belts,Nonexistent,
        CSV);

        Livewire::test(ImportCategories::class)
            ->set('uploadedAbsolutePath', $path)
            ->call('analyze')
            ->assertSet('preview.errorCount', 1)
            ->call('confirm');

        $this->assertSame(0, Category::count());

        @unlink($path);
    }

    public function test_sample_template_download_has_the_expected_header(): void
    {
        $response = (new ImportCategories)->downloadTemplate();

        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('name,parent,description,image', $csv);
        $this->assertStringContainsString('Sanding,Abrasives', $csv);
    }
}
