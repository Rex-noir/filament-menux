<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux;

use AceREx\FilamentMenux\Contracts\Enums\MenuxActionType;
use AceREx\FilamentMenux\Contracts\Enums\MenuxLinkTarget;
use AceREx\FilamentMenux\Contracts\Interfaces\ActionModifier;
use AceREx\FilamentMenux\Contracts\Interfaces\HasStaticDefaultValue;
use AceREx\FilamentMenux\Contracts\Interfaces\Menuxable;
use AceREx\FilamentMenux\Filament\Resources\Menus\MenuResource;
use AceREx\FilamentMenux\Filament\Resources\Menus\Schemas\MenuForm;
use AceREx\FilamentMenux\Filament\Resources\Menus\Schemas\MenuItemForm;
use AceREx\FilamentMenux\Filament\Resources\Menus\Tables\MenusTable;
use AceREx\FilamentMenux\Models\Menu;
use AceREx\FilamentMenux\Models\MenuItem;
use Filament\Actions\Action;
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

    protected string $menuModel = Menu::class;

    protected string $menuItemModel = MenuItem::class;

    protected Collection $actionModifiers;

    public function __construct()
    {
        // Lazy collection initialization ensures no shared static state.
        $this->staticMenuItems = collect();
        $this->menuxableModels = collect();
        $this->actionModifiers = collect();
    }

    public function getActionModifier(MenuxActionType $actionType): ?ActionModifier
    {
        return $this->actionModifiers->get($actionType->value);

    }

    public function hasActionModifier(MenuxActionType $actionType): bool
    {
        return $this->actionModifiers->has($actionType->value);

    }

    public function setActionModifier(MenuxActionType $actionType, string | ActionModifier $modifier): FilamentMenuxPlugin
    {
        if (is_string($modifier)) {
            if (! class_exists($modifier)) {
                throw new InvalidArgumentException("Class {$modifier} does not exist");
            }

            if (! in_array(ActionModifier::class, class_implements($modifier))) {
                throw new InvalidArgumentException("{$modifier} must implement " . ActionModifier::class . '.');
            }
            $modifier = app($modifier);
        }
        $this->actionModifiers->put($actionType->value, $modifier);

        return $this;

    }

    public function setActionModifierUsing(MenuxActionType $actionType, callable $modifier): FilamentMenuxPlugin
    {
        $modifierClass = new class($modifier) implements ActionModifier
        {
            private $modifier;

            public function __construct(callable $modifier)
            {
                $this->modifier = $modifier;
            }

            public function modify(Action $action): Action
            {
                $callable = $this->modifier;

                return $callable($action);

            }
        };
        $this->setActionModifier($actionType, $modifierClass);

        return $this;
    }

    public function getMenuModel(): string
    {
        return $this->menuModel;
    }

    public function setMenusModel(string $menuModel): FilamentMenuxPlugin
    {
        if (! class_exists($menuModel)) {
            throw new InvalidArgumentException("Model class {$menuModel} does not exist");
        }
        if (! is_subclass_of($menuModel, Menu::class)) {
            throw new InvalidArgumentException("Model class {$menuModel} must extend {$this->menuModel} class.");
        }
        $this->menuModel = $menuModel;

        return $this;
    }

    public function getMenuItemModel(): string
    {
        return $this->menuItemModel;
    }

    public function setMenuItemModel(string $menuItemModel): FilamentMenuxPlugin
    {
        if (! class_exists($menuItemModel)) {
            throw new InvalidArgumentException("Model class {$menuItemModel} does not exist");
        }
        if (! is_subclass_of($menuItemModel, MenuItem::class)) {
            throw new InvalidArgumentException("Model class {$menuItemModel} must extend {$this->menuItemModel} class.");
        }
        $this->menuItemModel = $menuItemModel;

        return $this;
    }

    /**
     * Getter for the {@see MenusTable} used throughout the plugin
     */
    public function getMenusTable(): string
    {
        return $this->menusTable;

    }

    /**
     * Customize the menus table which shows list of menus. By default, it contains one column, two actions and one bulk action.
     * The custom class must extend {@see MenusTable} or {@see \http\Exception\InvalidArgumentException} will be thrown
     *
     * @return $this
     */
    public function setMenusTable(string | callable $menusTable): FilamentMenuxPlugin
    {
        if (is_callable($menusTable)) {
            $menusTable = $menusTable();

            if (is_object($menusTable)) {
                $menusTable = get_class($menusTable);
            }
        }

        if (! class_exists($menusTable)) {
            throw new InvalidArgumentException("Table class {$menusTable} does not exist.");
        }

        if (! is_subclass_of($menusTable, MenusTable::class)) {
            throw new InvalidArgumentException("Table class {$menusTable} is not a valid table.");
        }

        $this->menusTable = $menusTable;

        return $this;
    }

    /**
     * Getter for the {@see MenuForm} used through the plugin.
     */
    public function getMenuForm(): string
    {
        return $this->menuForm;

    }

    /**
     * Setter for the menu form. By default, the form includes a single text input {@see MenuForm}.
     * If you want to customize it, the custom class must extend {@see MenuForm}}
     *
     * @return $this
     */
    public function setMenuForm(string | callable $menuForm): FilamentMenuxPlugin
    {
        if (is_callable($menuForm)) {
            $menuForm = $menuForm();

            if (is_object($menuForm)) {
                $menuForm = get_class($menuForm);
            }
        }

        if (! class_exists($menuForm)) {
            throw new InvalidArgumentException("Form class {$menuForm} does not exist.");
        }

        if (! is_subclass_of($menuForm, MenuForm::class)) {
            throw new InvalidArgumentException("Form class {$menuForm} is not a valid form.");
        }

        $this->menuForm = $menuForm;

        return $this;
    }

    /**
     * Getter for {@see MenuItemForm}
     */
    public function getMenuItemForm(): string
    {
        return $this->menuItemForm;
    }

    /**
     * Set your custom form for menu items. The menu form must extend {@see MenuItemForm} or {@see InvalidArgumentException} will be thrown
     *
     * @return $this
     */
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

    /**
     * Set the navigation label for {@see MenuResource}
     */
    public function setNavigationLabel(string $navigationLabel): FilamentMenuxPlugin
    {
        $this->navigationLabel = $navigationLabel;

        return $this;

    }

    /**
     * Get the navigation label of {@see MenuResource} unless modified via {@see FilamentMenuxPlugin->useCustomMenuResource()}
     */
    public function getNavigationLabel(): ?string
    {
        return $this->navigationLabel ?? 'Menus';
    }

    /**
     * Get the navigation group which {@see MenuResource} belongs to unless modified via custom {@see FilamentMenuxPlugin->useCustomMenuResource()}
     */
    public function getResourceNavigationGroup(): ?string
    {
        return $this->resourceNavigationGroup;

    }

    /**
     * Get the navigation icon of {@see MenuResource}
     */
    public function getNavigationIcon(): null | string | \BackedEnum
    {
        return $this->navigationIcon;

    }

    /**
     * To set the navigation icon of the {@see MenuResource}.
     *
     * @return $this
     */
    public function setNavigationIcon(string | null | \BackedEnum | callable $navigationIcon): FilamentMenuxPlugin
    {
        $result = is_callable($navigationIcon) ? $navigationIcon() : $navigationIcon;
        $this->navigationIcon = $result;

        return $this;
    }

    /**
     * To customize the menu resource, you can provide your own menu resource here. It must extend the base {@see MenuResource}
     *
     * @return $this
     */
    public function useCustomMenuResource(string $menuResource): FilamentMenuxPlugin
    {
        if (! class_exists($menuResource)) {
            throw new InvalidArgumentException("Resource class {$menuResource} does not exist");
        }

        if (! is_subclass_of($menuResource, MenuResource::class)) {
            throw new InvalidArgumentException("Resource class {$menuResource} is not a valid resource.");
        }

        $this->menuResource = $menuResource;

        return $this;

    }

    /**
     * Set the navigation group which the {@see MenuResource} belongs to.
     *
     * @return $this
     */
    public function setResourceNavigationGroup(string | callable | null $resourceNavigationGroup): FilamentMenuxPlugin
    {

        $this->resourceNavigationGroup = is_callable($resourceNavigationGroup) ? $resourceNavigationGroup() : $resourceNavigationGroup;

        return $this;

    }

    /**
     * Get the enum used for target in {@see MenuItem} model.
     * By default, it is set to {@see MenuxLinkTarget}}
     */
    public function getLinkTargetEnum(): string
    {
        return $this->linkTargetEnum;
    }

    /**
     * Set the link target enum used by {@see MenuItemForm} by default.
     * If you use custom {@see MenuItemForm} setting this is unnecessary since the form can have its own options for link target.
     * By default it is set to {@see MenuxLinkTarget}}
     *
     * @return $this
     */
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

    /**
     * Get how much to show in each pagination of {@see Menuxable} models.
     */
    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * Set how much to show in each pagination of {@see Menuxable} models.
     * By default, it is set to 4.
     *
     * @return $this
     */
    public function setPerPage(int $menuxablePerPage): FilamentMenuxPlugin
    {
        $this->perPage = $menuxablePerPage;

        return $this;
    }

    /**
     * Add or register the model for menu items.
     * The model must be subclass of {@see Model} and must implement {@see Menuxable} interface
     *
     * @return $this
     */
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

    /**
     * Get registered menuxable models.
     */
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
    public function addStaticMenuItem(string $label, string $url, \BackedEnum | string $target = MenuxLinkTarget::BLANK): FilamentMenuxPlugin
    {
        $this->staticMenuItems->put((string) Str::uuid(), compact('label', 'url', 'target'));

        return $this;
    }

    /**
     * Define static menus for the plugin.
     * When this is set and not empty, it will, by default, use a different create action showing only passed items as dialog.
     * The purpose should be for the admin user to be able to create only specified menus.
     *
     * @param  array<string, string>|callable(): array<string, string>  $slugs
     *                                                                          The array should map slugs to their corresponding labels,
     *                                                                          for example, ['home' => 'Home', 'about-us' => 'About Us'].
     */
    public function useStaticMenus(array | callable $slugs): FilamentMenuxPlugin
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
