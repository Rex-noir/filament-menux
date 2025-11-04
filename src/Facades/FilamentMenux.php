<?php

namespace AceREx\FilamentMenux\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AceREx\FilamentMenux\FilamentMenux
 */
class FilamentMenux extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AceREx\FilamentMenux\FilamentMenux::class;
    }
}
