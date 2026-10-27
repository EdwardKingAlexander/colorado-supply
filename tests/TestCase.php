<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    /**
     * Per-test SAM state directory, so tests never overwrite the real
     * sam-opportunities.json that the control panel and export read.
     */
    protected string $samStateDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->samStateDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'colorado-supply-sam-state-'.uniqid('', true);
        File::ensureDirectoryExists($this->samStateDirectory);
        config(['sam_opportunities.state_directory' => $this->samStateDirectory]);
    }

    protected function tearDown(): void
    {
        if (isset($this->samStateDirectory)) {
            File::deleteDirectory($this->samStateDirectory);
        }

        parent::tearDown();
    }
}
