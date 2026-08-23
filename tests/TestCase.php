<?php

namespace WaTemplates\Tests;

use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use WaTemplates\TemplatesServiceProvider;

/**
 * A bare Laravel application with just this package and Livewire booted.
 *
 * The point of testing standalone is to prove the package works without the
 * host: a second consumer inherits whatever this suite covers, and anything
 * that only passes inside `wa-connect` was never really the package's.
 */
abstract class TestCase extends Orchestra
{
    /**
     * Livewire first — `TemplatesServiceProvider::boot()` registers its
     * components only when `Livewire` exists, so a provider ordered ahead of it
     * would silently skip every `Livewire::component()` call and leave
     * `<livewire:wa-template-editor>` unresolvable.
     *
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            TemplatesServiceProvider::class,
        ];
    }

    /**
     * Livewire signs its component snapshots, so rendering one without an
     * `APP_KEY` dies with "No application encryption key has been specified" —
     * a failure about encryption that is really about a missing test harness.
     * Testbench ships no key of its own, so the suite sets one.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }
}
