<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux\Contracts\Interfaces;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
    public static function getMenuxablesUsing(Builder $builder, int $page, int $perPage, ?string $q): Builder | LengthAwarePaginator;

    /**
     * Returns groups with 'name' and 'query' keys.
     * 'query' should be a closure that accepts the same parameters as getMenuxablesUsing
     *
     * @return Collection<int, array{name: string, query: Closure(Builder,int, int, ?string): Builder|LengthAwarePaginator}>
     */
    public static function getMenuxableGroups(): Collection;
}
