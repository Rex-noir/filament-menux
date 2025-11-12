<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux\Livewire;

use AceREx\FilamentMenux\Contracts\Enums\MenuxEvents;
use AceREx\FilamentMenux\Contracts\Enums\MenuxLinkTarget;
use AceREx\FilamentMenux\Contracts\Interfaces\HasStaticDefaultValue;
use AceREx\FilamentMenux\Contracts\Interfaces\Menuxable;
use AceREx\FilamentMenux\FilamentMenuxPlugin;
use AceREx\FilamentMenux\Models\MenuItem;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\EmptyState;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Url;

class MenuItemTabs extends \Livewire\Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public string $menuId;

    public array $menuxables = [];

    public array $staticItems = [];

    public ?string $searchQuery = null;

    #[Url(as: 'tab')]
    public ?string $activeTab = null;

    public array $selectedItems = [];

    public array $groupedMenuItems = [];

    public array $groupedMenuxables = [];

    public function mount(string $menuId): void
    {
        $this->menuId = $menuId;
        $this->loadStaticItems();
        $this->loadMenuxables();
        $this->loadGroupedMenuItems();
    }

    private function loadStaticItems(): void
    {
        $plugin = FilamentMenuxPlugin::get();
        $staticMenuItems = $plugin->getStaticMenuItems();

        if ($staticMenuItems->isEmpty()) {
            $this->staticItems = [];

            return;
        }
        $this->staticItems = $staticMenuItems->map(function ($item, $id) {
            return [
                'id' => $id,
                'title' => $item['title'],
                'url' => $item['url'] ?? '#',
                'target' => $item['target']?->value ?? MenuxLinkTarget::SELF->value,
                'type' => 'static',
            ];
        })->toArray();
    }

    private function loadMenuxables(): void
    {
        $plugin = FilamentMenuxPlugin::get();
        $menuxableModels = $plugin->getMenuxableModels();

        if ($menuxableModels->isEmpty()) {
            return;
        }

        $this->menuxables = [];

        $menuxableModels->each(function (string $modelClass) {
            $this->buildMenuxableData($modelClass);
        });
    }

    public function updatedSearchQuery(): void
    {
        $this->selectedItems = [];
        $this->loadMenuxables();
    }

    /**
     * Normalize Builder or LengthAwarePaginator to the common menuxables array structure
     */
    private function normalizePaginationResult(Builder | \Illuminate\Contracts\Pagination\LengthAwarePaginator $result, int $perPage, int $page): array
    {
        return [
            'items' => collect($result->items())->map(fn ($item) => [
                'title' => $item->getMenuxTitle(),
                'url' => $item->getMenuxUrl(),
                'target' => $item->getMenuxTarget()->value,
                'type' => 'model',
                'id' => Str::uuid()->toString(),
            ])->toArray(),
            'current_page' => $result->currentPage(),
            'last_page' => $result->lastPage(),
            'per_page' => $result->perPage(),
            'total' => $result->total(),
        ];
    }

    private function buildMenuxableData(string $modelClass, int $page = 1, mixed $modelGroupId = null): void
    {
        $plugin = FilamentMenuxPlugin::get();
        $perPage = $plugin->getMenuxablesPerPage();

        /** @var Menuxable $modelClass */
        $groups = $modelClass::getMenuxableGroups();
        if ($groups->isNotEmpty()) {
            // Determine which groups to process
            $targetGroups = $modelGroupId
                ? $groups->filter(fn ($value, $label) => ($modelClass . ':' . $label) === $modelGroupId)
                : $groups;

            $targetGroups->each(function ($value, $label) use ($perPage, $page, $modelClass) {
                $result = $value($modelClass::query(), $page, $perPage, $this->searchQuery);
                $id = $modelClass . ':' . $label;

                if ($result instanceof \Illuminate\Database\Eloquent\Builder) {
                    $result = $result->paginate(perPage: $perPage, page: $page);
                } elseif (! ($result instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)) {
                    throw new \RuntimeException('Result must be a Builder or a Paginator instance.');
                }
                $this->menuxables[$modelClass] ??= [];

                $this->groupedMenuxables[$modelClass][$id] = [
                    'label' => $label,
                    'class' => $modelClass,
                    ...$this->normalizePaginationResult($result, $perPage, $page),
                ];
            });

            return;
        }

        // Fallback if no groups exist
        $result = $modelClass::getMenuxablesUsing($modelClass::query(), $page, $perPage, $this->searchQuery);
        if ($result instanceof \Illuminate\Database\Eloquent\Builder) {
            $result = $result->paginate(perPage: $perPage, page: $page);
        } elseif (! ($result instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)) {
            throw new \RuntimeException('Result must be a Builder or a Paginator instance.');
        }
        $this->menuxables[$modelClass] = $this->normalizePaginationResult($result, $perPage, $page);
    }

    public function goToPage(string $modelClass, int $page, mixed $groupId = null): void
    {
        $this->buildMenuxableData($modelClass, $page, $groupId);
    }

    private function getFilteredStaticItems(): array
    {
        if (empty($this->searchQuery)) {
            return $this->staticItems;
        }

        return collect($this->staticItems)
            ->filter(fn ($item) => str_contains(strtolower($item['title']), strtolower($this->searchQuery)))
            ->toArray();
    }

    private function getFilteredGroupMenuItems(string $group): array
    {
        if (! empty($this->searchQuery)) {
            $initialItems = $this->groupedMenuItems[$group] ?? [];

            return collect($initialItems)->filter(fn ($item) => str_contains(strtolower($item['title']), strtolower($this->searchQuery)))->toArray();

        }

        return $this->groupedMenuItems[$group] ?? [];
    }

    private function buildPaginationComponents(array $data, string $modelClass, mixed $groupId = null): Flex
    {
        $pagination = [];
        $pagination[] = Action::make('loadPrevious' . md5($modelClass))
            ->label(__('menux.actions.load_previous'))
            ->icon(icon: Heroicon::ChevronLeft)
            ->link()
            ->iconButton()
            ->disabled($data['current_page'] <= 1)
            ->action(fn () => $this->goToPage($modelClass, $data['current_page'] - 1, $groupId));

        $pagination[] = Text::make(__('menux.tabs.page_of', [
            'current' => $data['current_page'],
            'last' => $data['last_page'],
        ]));

        $pagination[] = Action::make('loadMore' . md5($modelClass))
            ->label(__('menux.actions.load_more'))
            ->icon(icon: Heroicon::ChevronRight)
            ->link()
            ->iconButton()
            ->extraAttributes(['class' => 'fi-ml-auto'])
            ->disabled($data['current_page'] >= $data['last_page'])
            ->action(fn () => $this->goToPage($modelClass, $data['current_page'] + 1, $groupId));

        return Flex::make($pagination)
            ->extraAttributes(['style' => 'text-align: center;'])
            ->columnSpanFull();
    }

    private function buildMenuxableTabSchema(string $modelClass, mixed $groupId = null): array
    {
        $data = [];
        if ($groupId !== null) {
            $data = $this->groupedMenuxables[$modelClass][$groupId] ?? [];

        } else {
            $data = $this->menuxables[$modelClass] ?? [];
        }

        $components = [];

        $pagination = $this->buildPaginationComponents($data, $modelClass, $groupId);

        if (empty($data['items'])) {
            $components[] = EmptyState::make(__('menux.tabs.no_items_found'))
                ->icon(icon: Heroicon::ExclamationCircle);

            return $components;
        }

        $options = collect($data['items'])->mapWithKeys(function ($item, $index) {
            return [$item['id'] => $item['title']];
        });

        $descriptions = collect($data['items'])->mapWithKeys(fn ($item, $index) => [$item['id'] => $item['url']])->toArray();
        $components[] = CheckboxList::make('menuxable_items')
            ->hiddenLabel()
            ->statePath('selectedItems')
            ->live()
            ->options($options->toArray())
            ->descriptions($descriptions);
        $components[] = $pagination;

        return $components;
    }

    private function getTabs(): array
    {
        $tabs = collect();
        $plugin = FilamentMenuxPlugin::get();
        $staticMenuItems = $plugin->getStaticMenuItems();
        $menuxableModels = $plugin->getMenuxableModels();
        $groupedMenuItems = $plugin->getGroupedMenuItems();

        if ($staticMenuItems->isNotEmpty()) {
            $tabs->push(
                Tab::make('Static')
                    ->label(function () use ($plugin) {
                        if ($plugin->getStaticTabTitle() !== null) {
                            return $plugin->getStaticTabTitle();
                        }

                        return __('menux.tabs.static');
                    })
                    ->schema(function () {
                        $filteredItems = $this->getFilteredStaticItems();

                        if (empty($filteredItems)) {
                            return [
                                EmptyState::make(__('menux.tabs.no_items_found'))
                                    ->icon(icon: Heroicon::ExclamationCircle),
                            ];
                        }

                        return [
                            CheckboxList::make('static_items')
                                ->hiddenLabel()
                                ->statePath('selectedItems')
                                ->live()
                                ->options(collect($filteredItems)->mapWithKeys(fn ($item) => [$item['id'] => $item['title']]))
                                ->descriptions(collect($filteredItems)->mapWithKeys(fn ($item) => [$item['id'] => $item['url']])),
                        ];
                    })
            );
        }
        if ($groupedMenuItems->isNotEmpty()) {
            foreach ($groupedMenuItems as $group => $items) {
                $tabs->push(
                    Tab::make($group)
                        ->label($group)
                        ->schema(function () use ($group) {
                            $filteredItems = $this->getFilteredGroupMenuItems($group);

                            if (empty($filteredItems)) {
                                return [
                                    EmptyState::make(__('menux.tabs.no_items_found'))
                                        ->icon(icon: Heroicon::ExclamationCircle),
                                ];
                            }

                            return [
                                CheckboxList::make('')
                                    ->hiddenLabel()
                                    ->statePath('selectedItems')
                                    ->live()
                                    ->options(collect($filteredItems)->mapWithKeys(fn ($item) => [$item['id'] => $item['title']]))
                                    ->descriptions(collect($filteredItems)->mapWithKeys(fn ($item) => [$item['id'] => $item['url']])),
                            ];
                        })
                );
            }
        }

        if ($menuxableModels->isNotEmpty()) {

            $menuxableModels->each(callback: function (string $modelClass) use ($tabs) {
                $group = $this->groupedMenuxables[$modelClass] ?? null;
                if ($group !== null) {
                    foreach ($group as $id => $data) {
                        $tabs->push(Tab::make($data['label'])
                            ->id($id)
                            ->schema(fn () => $this->buildMenuxableTabSchema($data['class'], $id)));
                    }
                } else {
                    /** @var Menuxable $modelClass */
                    $tabs->push(
                        Tab::make($modelClass::getMenuxLabel())
                            ->id($modelClass)
                            ->schema(function () use ($modelClass) {
                                /** @var string $modelClass */
                                return $this->buildMenuxableTabSchema($modelClass);
                            })
                    );
                }
            });

            return $tabs->toArray();
        }

        return [];
    }

    public function addMenuItems(): void
    {
        /** @var MenuItem $itemModel */
        $itemModel = FilamentMenuxPlugin::get()->getMenuItemModel();
        $plugin = FilamentMenuxPlugin::get();
        /** @var BackedEnum&HasStaticDefaultValue $enum */
        $enum = $plugin->getLinkTargetEnum();

        $items = collect($this->staticItems);
        collect($this->menuxables)->each(function ($data, $modelClass) use (&$items) {
            $group = $this->groupedMenuxables[$modelClass] ?? null;
            if (! empty($group)) {
                $items = $items->merge(collect($group)->flatMap(fn ($groupData) => collect($groupData['items'])->mapWithKeys(fn ($item, $index) => [$item['id'] => $item])));

                return;
            }
            $items = $items->merge(collect($data['items'])->mapWithKeys(fn ($item, $index) => [$item['id'] => $item]));
        });
        collect($this->groupedMenuItems)->each(function ($data, $group) use (&$items) {
            $items = $items->merge(collect($data)->mapWithKeys(fn ($item, $index) => [$item['id'] => $item]));
        });

        $itemsToAdd = collect($this->selectedItems)->mapWithKeys(function ($item, $index) use ($items, $enum) {
            $itemData = $items->get($item);
            $itemData['target'] = $enum::tryFrom($itemData['target']) ?? $enum::getStaticDefaultValue();
            $itemData['menu_id'] = $this->menuId;
            unset($itemData['id']);
            unset($itemData['type']);

            return [$item => $itemData];
        });

        collect($itemsToAdd->values())->each(function ($data) use ($itemModel) {
            $itemModel::create($data);
        });
        $this->dispatch(MenuxEvents::CREATED->value, menuId: $this->menuId, ids: $itemsToAdd->keys()->toArray());

        Notification::make('success')
            ->title(__('menux.notifications.items_added.title'))
            ->success()
            ->body(__('menux.notifications.items_added.body', ['count' => count($itemsToAdd)]))
            ->send();
        // Reset state
        $this->selectedItems = [];
        $this->searchQuery = null;
    }

    public function menuItemFormSchema(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->compact()
                    ->footerActions([
                        Action::make('addItems')
                            ->label(fn () => count($this->selectedItems) > 0 ? __('menux.actions.add_selected', ['count' => count($this->selectedItems)]) : __('menux.actions.add_items'))
                            ->disabled(fn () => empty($this->selectedItems))
                            ->action('addMenuItems'),
                    ])
                    ->secondary()
                    ->schema([
                        TextInput::make('search')
                            ->label(__('menux.labels.search'))
                            ->hiddenLabel()
                            ->placeholder(__('menux.placeholders.search'))
                            ->statePath('searchQuery')
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn () => $this->updatedSearchQuery()),
                        Tabs::make('tabs')
                            ->tabs($this->getTabs())
                            ->persistTabInQueryString('tab')
                            ->contained(),
                    ]),
            ]);
    }

    public function render(): View
    {
        return view('filament-menux::livewire.menu-item-tabs');
    }

    private function loadGroupedMenuItems(): void
    {
        $plugin = FilamentMenuxPlugin::get();
        $this->groupedMenuItems = $plugin->getGroupedMenuItems()->mapWithKeys(function ($items, $group) {
            return [
                $group => collect($items)->map(function ($item) {
                    return [
                        'id' => Str::uuid()->toString(),
                        'title' => $item['title'],
                        'url' => $item['url'] ?? '#',
                        'target' => $item['target']?->value ?? MenuxLinkTarget::SELF->value,
                        'type' => 'static',
                    ];
                }),
            ];
        })->toArray();
    }
}
