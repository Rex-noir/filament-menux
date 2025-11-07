<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux\Contracts\Interfaces;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @method static query()
 */
interface Menuxable
{
    public static function getMenuxLabel(): string;

    public function getMenuxTitle(): string;

    public function getMenuxUrl(): string;

    public function getMenuxTarget(): \BackedEnum;

    /**
     * You can also do your own pagination instead of returning the builder instance
     */
    public static function getMenuxablesUsing(int $page, int $perPage, ?string $q, Builder $builder): Builder | LengthAwarePaginator;
}
