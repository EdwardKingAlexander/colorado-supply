<?php

namespace Tests\Support;

use App\Services\PubLog\PubLogImporter;
use Illuminate\Support\Facades\File;
use ZipArchive;

class PubLogFixtures
{
    public static function archives(string $directory, ?callable $change = null): string
    {
        File::ensureDirectoryExists($directory);
        $files = [];
        foreach (glob(base_path('tests/Fixtures/publog/*.CSV')) as $file) {
            $files[basename($file)] = file_get_contents($file);
        }
        $files = $change ? $change($files) : $files;
        $members = [
            'IDENTIFICATION' => ['P_FLIS_NSN.CSV', 'P_HELP.CSV', 'V_FLIS_STANDARDIZATION.CSV', 'V_FLIS_CANCELLED_NIIN.CSV'],
            'CAGE' => ['P_CAGE.CSV', 'V_CAGE_ADDRESS.CSV'],
            'REFERENCE' => ['V_FLIS_PART.CSV'],
        ];
        foreach ($members as $archive => $names) {
            $zip = new ZipArchive;
            $zip->open($directory.'/'.$archive.'.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
            foreach ($names as $name) {
                if (isset($files[$name])) {
                    $zip->addFromString($name, $files[$name]);
                    $zip->setMtimeName($name, strtotime('2026-09-28 12:00:00 UTC'));
                }
            }
            $zip->close();
        }

        return $directory;
    }

    public static function ready(string $directory, string $release = '2026-10-01', ?callable $change = null)
    {
        self::archives($directory, $change);
        $service = app(PubLogImporter::class);

        return $service->run($service->prepare($directory, $release));
    }
}
