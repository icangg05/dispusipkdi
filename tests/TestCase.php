<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Refuse to wipe a real database: tests only ever refresh SQLite in memory.
     *
     * @return array<class-string, class-string>
     */
    protected function setUpTraits()
    {
        $connection = config('database.default');

        if (isset(class_uses_recursive(static::class)[RefreshDatabase::class])
            && ($connection !== 'sqlite' || config("database.connections.$connection.database") !== ':memory:')) {
            throw new RuntimeException("Tests must run on SQLite in memory, not [$connection]. Check phpunit.xml.");
        }

        return parent::setUpTraits();
    }
}
