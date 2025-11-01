<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux;

use AceREx\FilamentMenux\Filament\Resources\Menus\MenuResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Collection;

class FilamentMenuxPlugin implements Plugin
{
    protected ?Collection $staticMenus = null;

    protected $menuResource = MenuResource::class;

    /**
     * Define static menus for the plugin.
     *
     * You can pass either:
     * - An associative array of slug => label pairs, e.g. ['dashboard' => 'Dashboard', 'users' => 'Users']
     * - Or a callable returning such an array.
     *
     * @param array<string, string>|callable(): array<string, string> $slugs
     * @return static
     */
    public function useStaticMenus(array|callable $slugs): static
    {
        $menus = is_callable($slugs) ? $slugs() : $slugs;
        $this->staticMenus = collect($menus);
        return $this;
    }

    public function getStaticMenus(): ?Collection
    {
        return $this->staticMenus;
    }

    public function getId(): string
    {
        return 'filament-menux';
    }


    public function register(Panel $panel): void
    {
        $panel->resources([
            $this->menuResource
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
