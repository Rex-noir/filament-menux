<?php

namespace AceREx\FilamentMenux\Contracts\Traits;

use AceREx\FilamentMenux\Contracts\Enums\MenuxLinkTarget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

trait HasMenuxable
{
    public static function getMenuxableGroups(): Collection
    {
        return collect([]);
    }

    public static function getMenuxablesUsing(Builder $builder, int $page, int $perPage, ?string $q): Builder | LengthAwarePaginator
    {
        return $builder;
    }

    public function getMenuxTarget(): \BackedEnum
    {
        return MenuxLinkTarget::SELF;
    }
}
