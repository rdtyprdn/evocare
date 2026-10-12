<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Pengaman: test TIDAK BOLEH menyentuh database asli (MySQL/MAMP).
        $default = config('database.default');
        if ($default !== 'sqlite' || config("database.connections.$default.database") !== ':memory:') {
            throw new RuntimeException('Test dihentikan: database test harus sqlite :memory:, bukan '.$default.'.');
        }
    }
}
