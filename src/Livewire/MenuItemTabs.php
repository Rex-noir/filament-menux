<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux\Livewire;

use AceREx\FilamentMenux\Contracts\Enums\MenuItemTarget;
use AceREx\FilamentMenux\Contracts\Enums\MenuxEvents;
use AceREx\FilamentMenux\Contracts\Interfaces\Menuxable;
use AceREx\FilamentMenux\FilamentMenuxPlugin;
use AceREx\FilamentMenux\Models\MenuItem;
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
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\View\View;
use JetBrains\PhpStorm\NoReturn;
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

    public function mount(string $menuId): void
    {
        $this->menuId = $menuId;
        $this->loadStaticItems();
        $this->loadMenuxables();
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
                'title' => $item['label'],
                'url' => $item['url'] ?? '#',
                'target' => $item['target']?->value ?? MenuItemTarget::SELF->value,
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

        if ($this->activeTab === 'Static') {
            // Static items filtering happens in schema
        } elseif ($this->activeTab && class_exists($this->activeTab)) {
            $this->buildMenuxableData($this->activeTab, 1);
        } else {
            $this->loadMenuxables();
        }
    }

    private function buildMenuxableData(string $modelClass, int $page = 1): void
    {
        /** @var Menuxable $modelClass */
        $pagination = $modelClass::getMenuxablesUsing($this->searchQuery, $modelClass::query())->paginate(4, page: $page);
        $this->menuxables[$modelClass] = [
            'items' => collect($pagination->items())->map(function ($item) {
                return [
                    'title' => $item->getMenuxTitle(),
                    'url' => $item->getMenuxUrl(),
                    'target' => $item->getMenuxTarget()->value,
                    'type' => 'model',
                    'id' => \Str::uuid()->toString(),
                ];
            })->toArray(),
            'current_page' => $pagination->currentPage(),
            'last_page' => $pagination->lastPage(),
            'per_page' => $pagination->perPage(),
            'total' => $pagination->total(),
        ];
    }

    public function goToPage(string $modelClass, int $page): void
    {
        $this->buildMenuxableData($modelClass, $page);
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

    private function getTabs(): array
    {
        $tabs = collect();
        $plugin = FilamentMenuxPlugin::get();
        $staticMenuItems = $plugin->getStaticMenuItems();
        $menuxableModels = $plugin->getMenuxableModels();

        if ($staticMenuItems->isNotEmpty()) {
            $tabs->push(
                Tab::make('Static')
                    ->schema(function () {
                        $filteredItems = $this->getFilteredStaticItems();

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

        if ($menuxableModels->isNotEmpty()) {
            $menuxableModels->each(callback: function (string $modelClass) use ($tabs) {
                /** @var Menuxable $modelClass */
                $tabs->push(
                    Tab::make($modelClass::getMenuxLabel())
                        ->id($modelClass)
                        ->schema(function () use ($modelClass) {
                            /** @var string $modelClass */
                            $data = $this->menuxables[$modelClass] ?? [];
                            if (empty($data)) {
                                return [];
                            }

                            $pagination = [];
                            /** @var string $modelClass */
                            $pagination[] = Action::make('loadPrevious')
                                ->label('Load Previous')
                                ->icon(icon: Heroicon::ChevronLeft)
                                ->link()
                                ->iconButton()
                                ->disabled($data['current_page'] <= 1)
                                ->action(fn () => $this->goToPage($modelClass, $data['current_page'] - 1));

                            $pagination[] = Text::make("Page {$data['current_page']} of {$data['last_page']}");

                            /** @var string $modelClass */
                            $pagination[] = Action::make('loadMore')
                                ->label('Load More')
                                ->icon(icon: Heroicon::ChevronRight)
                                ->link()
                                ->iconButton()
                                ->extraAttributes(['class' => 'fi-ml-auto'])
                                ->disabled($data['current_page'] >= $data['last_page'])
                                ->action(fn () => $this->goToPage($modelClass, $data['current_page'] + 1));

                            /** @var string $modelClass */
                            $options = collect($data['items'])
                                ->mapWithKeys(function ($item, $index) {
                                    return [$item['id'] => $item['title']];
                                });
                            $descriptions = collect($data['items'])->mapWithKeys(fn ($item, $index) => [$item['id'] => $item['url']])->toArray();
                            $components = [];
                            if ($options->isNotEmpty()) {
                                $components[] =
                                    CheckboxList::make('menuxable_items')
                                        ->hiddenLabel()
                                        ->statePath('selectedItems')
                                        ->live()
                                        ->options($options->toArray())
                                        ->descriptions($descriptions);
                            } else {
                                /** @var Menuxable $modelClass */
                                $components[] = EmptyState::make("No items found for {$modelClass::getMenuxLabel()}")
                                    ->icon(icon: Heroicon::ExclamationCircle);
                            }

                            if (! empty($pagination)) {
                                $components[] = Flex::make($pagination)
                                    ->extraAttributes(['style' => 'text-align: center;'])
                                    ->columnSpanFull();
                            }

                            return $components;
                        })
                );
            });
        }

        return $tabs->toArray();
    }

    #[NoReturn]
    public function addMenuItems(): void
    {
        $items = collect($this->staticItems);
        collect($this->menuxables)->each(function ($data, $modelClass) use (&$items) {
            $items = $items->merge(collect($data['items'])->mapWithKeys(fn ($item, $index) => [$item['id'] => $item]));
        });

        $itemsToAdd = collect($this->selectedItems)->mapWithKeys(function ($item, $index) use ($items) {
            $itemData = $items->get($item);
            $itemData['target'] = MenuItemTarget::tryFrom($itemData['target']) ?? MenuItemTarget::SELF;
            $itemData['menu_id'] = $this->menuId;
            unset($itemData['id']);
            unset($itemData['type']);

            return [$item => $itemData];
        });

        if (empty($itemsToAdd)) {
            return;
        }
        collect($itemsToAdd->values())->each(function ($data) {
            MenuItem::create($data);
        });
        $this->dispatch(MenuxEvents::CREATED->value, menuId: $this->menuId, ids: $itemsToAdd->keys()->toArray());

        Notification::make('success')
            ->title('Menu items added successfully')
            ->success()
            ->body("Total items added: {$itemsToAdd->count()}")
            ->send();
        // Reset state
        $this->selectedItems = [];
        $this->searchQuery = null;
    }

    public function menuItemFormSchema(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Menu Items')
                    ->headerActions([
                        Action::make('newItem')
                            ->icon(icon: Heroicon::PlusCircle)
                            ->label('New Custom Menu Item')
                            ->iconButton()
                            ->modalHeading('Add custom menu items directly')
                            ->modalWidth(width: Width::Small)
                            ->schema(\AceREx\FilamentMenux\Filament\Resources\Menus\Schemas\MenuItemForm::make())
                            ->action(function ($data) {
                                MenuItem::query()->create(array_merge($data, ['menu_id' => $this->menuId]));
                                Notification::make('menuItemCreated')
                                    ->success()
                                    ->title('Menu item created successfully')
                                    ->send();
                                $this->dispatch(MenuxEvents::CREATED->value, menuId: $this->menuId, ids: [$data['title']]);
                            }),
                    ])
                    ->compact()
                    ->footerActions([
                        Action::make('addItems')
                            ->label(fn () => count($this->selectedItems) > 0 ? 'Add ' . count($this->selectedItems) . ' Selected Items' : 'Add Menu Items')
                            ->disabled(fn () => empty($this->selectedItems))
                            ->action('addMenuItems'),
                    ])
                    ->secondary()
                    ->schema([
                        TextInput::make('search')
                            ->label('Search')
                            ->hiddenLabel()
                            ->placeholder('Search menu items...')
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
}
