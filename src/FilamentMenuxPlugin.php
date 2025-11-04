<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux;

use AceREx\FilamentMenux\Contracts\Enums\MenuItemTarget;
use AceREx\FilamentMenux\Contracts\Interfaces\Menuxable;
use AceREx\FilamentMenux\Filament\Resources\Menus\MenuResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class FilamentMenuxPlugin implements Plugin
{
    /**
     * Cached collection of static menus defined for the plugin.
     *
     * @var Collection<string, string>|null
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
     * @var Collection<string, array{label: string, url: string}>
     */
    protected Collection $staticMenuItems;

    protected Collection $menuxableModels;

    protected string $linkTargetEnum = MenuItemTarget::class;

    protected int $perPage = 4;

    public function __construct()
    {
        // Lazy collection initialization ensures no shared static state.
        $this->staticMenuItems = collect();
        $this->menuxableModels = collect();
    }

    public function getLinkTargetEnum(): string
    {
        return $this->linkTargetEnum;

    }

    public function setLinkTargetEnum(string $linkTargetEnum): FilamentMenuxPlugin
    {
        if (! enum_exists($linkTargetEnum)) {
            throw new InvalidArgumentException("Enum class {$linkTargetEnum} does not exist");
        }

        if (! in_array(HasLabel::class, class_implements($linkTargetEnum), true)) {
            throw new InvalidArgumentException("{$linkTargetEnum} must implement " . HasLabel::class . '.');
        }
        $this->linkTargetEnum = $linkTargetEnum;

        return $this;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function setPerPage(int $menuxablePerPage): FilamentMenuxPlugin
    {
        $this->perPage = $menuxablePerPage;

        return $this;
    }

    public function addMenuxableModel(string $model): FilamentMenuxPlugin
    {
        if (! class_exists($model)) {
            throw new InvalidArgumentException("Model class {$model} does not exist");
        }

        if (! is_subclass_of($model, Model::class)) {
            throw new InvalidArgumentException("Model class {$model} is not a valid model");
        }

        if (! in_array(Menuxable::class, class_implements($model))) {
            throw new InvalidArgumentException("{$model} must implement " . Menuxable::class . '.');
        }

        $this->menuxableModels->push($model);

        return $this;
    }

    public function getMenuxableModels(): Collection
    {
        return $this->menuxableModels;
    }

    /**
     * Retrieve all registered static menu items, ensuring unique URLs.
     *
     * @return Collection<int, array{label: string, url: string}>
     */
    public function getStaticMenuItems(): Collection
    {
        return $this->staticMenuItems->unique('url');
    }

    /**
     * Register a new static menu item for the plugin.
     *
     * @param  string  $label  The display name of the menu item.
     * @param  string  $url  The target URL for the menu item.
     */
    public function addStaticMenuItem(string $label, string $url): static
    {
        $this->staticMenuItems->put((string) Str::uuid(), compact('label', 'url'));

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
     * @return Collection<string, string>|null
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
    public static function make(): FilamentMenuxPlugin
    {
        return app(FilamentMenuxPlugin::class);
    }

    /**
     * Retrieve the active plugin instance registered in Filament.
     */
    public static function get(): FilamentMenuxPlugin
    {
        /** @var static $plugin */
        $plugin = filament(app(FilamentMenuxPlugin::class)->getId());

        return $plugin;
    }
}
