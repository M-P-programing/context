<?php

namespace Altra\Context\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Altra\Context\ContextServiceProvider;
use Astrotomic\Translatable\TranslatableServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();


        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Altra\\Context\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            ContextServiceProvider::class,
            TranslatableServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        
        $migration = include __DIR__.'/../database/migrations/create_test_migrations.php.stub';
        $migration->up();
        
    }
}
