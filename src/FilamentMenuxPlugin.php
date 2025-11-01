<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux;

use AceREx\FilamentMenux\Filament\Resources\Menus\MenuResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Collection;

final class FilamentMenuxPlugin implements Plugin
{
    /**
     * Cached collection of static menus defined for the plugin.
     *
     * @var \Illuminate\Support\Collection<string, string>|null
     */
    protected ?Collection $staticMenus = null;

    /**
     * Fully qualified class name of the associated Filament resource.
     *
     * @var class-string<MenuResource>
     */
    protected string $menuResource = MenuResource::class;

    /**
     * Holds statically defined menu items with labels and URLs.
     *
     * @var \Illuminate\Support\Collection<int, array{label: string, url: string}>
     */
    protected Collection $staticMenuItems;

    public function __construct()
    {
        // Lazy collection initialization ensures no shared static state.
        $this->staticMenuItems = collect();
    }

    /**
     * Retrieve all registered static menu items, ensuring unique URLs.
     *
     * @return \Illuminate\Support\Collection<int, array{label: string, url: string}>
     */
    public function getStaticMenuItems(): Collection
    {
        return $this->staticMenuItems->unique('url')->values();
    }

    /**
     * Register a new static menu item for the plugin.
     *
     * @param  string  $label  The display name of the menu item.
     * @param  string  $url  The target URL for the menu item.
     */
    public function addStaticMenuItem(string $label, string $url): static
    {
        $this->staticMenuItems->push(compact('label', 'url'));

        return $this;
    }

    /**
     * Define static menus for the plugin.
     *
     * @param  array<string, string>|callable(): array<string, string>  $slugs
     */
    public function useStaticMenus(array | callable $slugs): static
    {
        $menus = is_callable($slugs) ? $slugs() : $slugs;
        $this->staticMenus = collect($menus);

        return $this;
    }

    /**
     * Retrieve the currently defined static menus.
     *
     * @return \Illuminate\Support\Collection<string, string>|null
     */
    public function getStaticMenus(): ?Collection
    {
        return $this->staticMenus;
    }

    /**
     * Get the unique plugin identifier.
     */
    public function getId(): string
    {
        return 'filament-menux';
    }

    /**
     * Register plugin resources with the given Filament panel.
     */
    public function register(Panel $panel): void
    {
        $panel->resources([$this->menuResource]);
    }

    /**
     * Perform any post-registration boot logic for the plugin.
     */
    public function boot(Panel $panel): void
    {
        // Reserved for plugin runtime hooks or bootstrapping logic.
    }

    /**
     * Create a new plugin instance through the service container.
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Retrieve the active plugin instance registered in Filament.
     */
    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
