<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux;

use AceREx\FilamentMenux\Contracts\Enums\MenuxLinkTarget;
use AceREx\FilamentMenux\Contracts\Interfaces\HasStaticDefaultValue;
use AceREx\FilamentMenux\Contracts\Interfaces\Menuxable;
use AceREx\FilamentMenux\Filament\Resources\Menus\MenuResource;
use AceREx\FilamentMenux\Filament\Resources\Menus\Schemas\MenuForm;
use AceREx\FilamentMenux\Filament\Resources\Menus\Schemas\MenuItemForm;
use AceREx\FilamentMenux\Filament\Resources\Menus\Tables\MenusTable;
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

    protected string $linkTargetEnum = MenuxLinkTarget::class;

    protected int $perPage = 4;

    protected ?string $resourceNavigationGroup = null;

    protected null | string | \BackedEnum $navigationIcon = null;

    protected ?string $navigationLabel = null;

    protected string $menuItemForm = MenuItemForm::class;

    protected string $menuForm = MenuForm::class;

    protected string $menusTable = MenusTable::class;

    public function __construct()
    {
        // Lazy collection initialization ensures no shared static state.
        $this->staticMenuItems = collect();
        $this->menuxableModels = collect();
    }

    public function getMenusTable(): string
    {
        return $this->menusTable;

    }

    public function setMenusTable(string $menusTable): FilamentMenuxPlugin
    {
        if (! class_exists($menusTable)) {
            throw new InvalidArgumentException("Table class {$menusTable} does not exist");
        }
        if (! is_subclass_of($menusTable, MenusTable::class)) {
            throw new InvalidArgumentException("Table class {$menusTable} is not a valid table.");
        }
        $this->menusTable = $menusTable;

        return $this;
    }

    public function getMenuForm(): string
    {
        return $this->menuForm;

    }

    public function setMenuForm(string $menuForm): FilamentMenuxPlugin
    {
        if (! class_exists($menuForm)) {
            throw new InvalidArgumentException("Form class {$menuForm} does not exist");
        }
        if (! is_subclass_of($menuForm, MenuForm::class)) {
            throw new InvalidArgumentException("Form class {$menuForm} is not a valid form.");
        }
        $this->menuForm = $menuForm;
    }

    public function getMenuItemForm(): string
    {
        return $this->menuItemForm;
    }

    public function setMenuItemForm(string $menuItemForm): FilamentMenuxPlugin
    {
        if (! class_exists($menuItemForm)) {
            throw new InvalidArgumentException("Form class {$menuItemForm} does not exist");
        }
        if (! is_subclass_of($menuItemForm, MenuItemForm::class)) {
            throw new InvalidArgumentException("Form class {$menuItemForm} is not a valid form.");
        }
        $this->menuItemForm = $menuItemForm;

        return $this;

    }

    public function setNavigationLabel(string $navigationLabel): FilamentMenuxPlugin
    {
        $this->navigationLabel = $navigationLabel;

        return $this;

    }

    public function getNavigationLabel(): ?string
    {
        return $this->navigationLabel ?? 'Menus';
    }

    public function getResourceNavigationGroup(): ?string
    {
        return $this->resourceNavigationGroup;

    }

    public function getNavigationIcon(): null | string | \BackedEnum
    {
        return $this->navigationIcon;

    }

    public function setNavigationIcon(string | null | \BackedEnum | callable $navigationIcon): FilamentMenuxPlugin
    {
        $result = is_callable($navigationIcon) ? $navigationIcon() : $navigationIcon;
        $this->navigationIcon = $result;

        return $this;
    }

    public function setResourceNavigationGroup(string | callable | null $resourceNavigationGroup): FilamentMenuxPlugin
    {

        $this->resourceNavigationGroup = is_callable($resourceNavigationGroup) ? $resourceNavigationGroup() : $resourceNavigationGroup;

        return $this;

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

        $implements = class_implements($linkTargetEnum);

        if (
            ! isset($implements[HasLabel::class]) ||
            ! isset($implements[HasStaticDefaultValue::class])
        ) {
            throw new InvalidArgumentException("{$linkTargetEnum} must implement both HasLabel and HasStaticDefaultValue.");
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
