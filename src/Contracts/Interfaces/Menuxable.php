<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux\Contracts\Interfaces;

use AceREx\FilamentMenux\Contracts\Enums\MenuItemTarget;
use Illuminate\Database\Eloquent\Builder;

interface Menuxable
{
    public static function getMenuxLabel(): string;

    public function getMenuxTitle(): string;

    public function getMenuxUrl(): string;

    public function getMenuxTarget(): MenuItemTarget;

    public static function getMenuxablesUsing(?string $q, Builder $builder): Builder;
}
