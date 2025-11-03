<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux\Livewire;

use AceREx\FilamentMenux\Contracts\Enums\MenuItemTarget;
use AceREx\FilamentMenux\Contracts\Interfaces\Menuxable;
use AceREx\FilamentMenux\FilamentMenuxPlugin;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
use Livewire\Attributes\Url;

class MenuItemForm extends \Livewire\Component implements HasActions, HasSchemas
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
                'label' => $item['label'],
                'url' => $item['url'] ?? '#',
                'target' => $item['target'] ?? MenuItemTarget::SELF->value,
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
        // Reset selections when search changes
        $this->selectedItems = [];

        // Determine which tab is active and rebuild data accordingly
        if ($this->activeTab === 'Static') {
            // Static items are already loaded, just filter in the schema
            // No need to rebuild
        } elseif ($this->activeTab && class_exists($this->activeTab)) {
            // Active tab is a menuxable model class
            $this->buildMenuxableData($this->activeTab, 1);
        } else {
            // If no active tab is set, rebuild all menuxables
            $this->loadMenuxables();
        }
    }

    /**
     * Build the menuxable data structure for a single model class.
     *
     * @param  class-string<Menuxable>  $modelClass
     */
    private function buildMenuxableData(string $modelClass, int $page = 1): void
    {
        $pagination = $modelClass::getMenuxablesUsing($this->searchQuery, $modelClass::query())->paginate(5, page: $page);
        $this->menuxables[$modelClass] = [
            'items' => collect($pagination->items())->map(function ($item) {
                return [
                    'title' => $item->getMenuxTitle(),
                    'url' => $item->getMenuxUrl(),
                    'target' => $item->getMenuxTarget()->value,
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
        // Reset selections when page changes
        $this->selectedItems = [];

        $this->buildMenuxableData($modelClass, $page);
    }

    private function getFilteredStaticItems(): array
    {
        if (empty($this->searchQuery)) {
            return $this->staticItems;
        }

        return collect($this->staticItems)
            ->filter(function ($item) {
                return str_contains(
                    strtolower($item['label']),
                    strtolower($this->searchQuery)
                );
            })
            ->toArray();
    }

    public function onStateSelected($selected): void
    {
        $oldActiveTab = $this->activeTab;
        if ($oldActiveTab && $this->activeTab !== $oldActiveTab) {
            dd('HI');
            $this->selectedItems = [];
            $this->selectedItems[] = $selected;
        }
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
                            CheckboxList::make('static_menu_items')
                                ->hiddenLabel()
                                ->statePath('selectedItems')
                                ->live()
                                ->afterStateUpdated(function ($state) {
                                    $this->onStateSelected($state);
                                })
                                ->options(collect($filteredItems)->mapWithKeys(function ($item) {
                                    return [$item['id'] => $item['label']];
                                }))
                                ->descriptions(collect($filteredItems)->mapWithKeys(function ($item) {
                                    return [$item['id'] => $item['url']];
                                })),
                        ];
                    })
            );
        }

        if ($menuxableModels->isNotEmpty()) {
            $menuxableModels->each(function (string $modelClass) use ($tabs) {
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
                            $pagination[] = Action::make('loadPrevious')
                                ->label('Load Previous')
                                ->icon(icon: Heroicon::ChevronLeft)
                                ->link()
                                ->iconButton()
                                ->disabled($data['current_page'] <= 1)
                                ->action(function () use ($modelClass, $data) {
                                    $this->goToPage($modelClass, $data['current_page'] - 1);
                                });

                            $pagination[] = Text::make("Page {$data['current_page']} of {$data['last_page']}");

                            $pagination[] = Action::make('loadMore')
                                ->label('Load More')
                                ->icon(icon: Heroicon::ChevronRight)
                                ->link()
                                ->iconButton()
                                ->extraAttributes(['class' => 'fi-ml-auto'])
                                ->disabled($data['current_page'] >= $data['last_page'])
                                ->action(function () use ($modelClass, $data) {
                                    $this->goToPage($modelClass, $data['current_page'] + 1);
                                });

                            $components = [
                                CheckboxList::make('menuxable_items')
                                    ->hiddenLabel()
                                    ->statePath('selectedItems')
                                    ->options(function () use ($data) {
                                        return collect($data['items'])->mapWithKeys(fn ($item, $index) => [$index => $item['title']])->toArray();
                                    })
                                    ->live()
                                    ->afterStateUpdated(function ($state) {
                                        $this->onStateSelected($state);
                                    })
                                    ->descriptions(function () use ($data) {
                                        return collect($data['items'])->mapWithKeys(fn ($item, $index) => [$index => $item['url']])->toArray();
                                    }),
                            ];

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

    public function addMenuItems(): void
    {
        $this->getActiveTab();
        if (empty($this->selectedItems)) {
            return;
        }

        // Validate and process based on active tab
        if ($this->activeTab === 'Static') {
            $this->processStaticSelections();
        } elseif ($this->activeTab && class_exists($this->activeTab)) {
            $this->processMenuxableSelections($this->activeTab);
        }

        // Reset after processing
        $this->selectedItems = [];
        $this->searchQuery = null;
    }

    private function processStaticSelections(): void
    {
        $validStaticIds = collect($this->staticItems)->pluck('id')->toArray();

        $selectedStaticItems = collect($this->selectedItems)
            ->filter(fn ($selected, $id) => in_array($id, $validStaticIds))
            ->map(fn ($selected, $id) => collect($this->staticItems)->firstWhere('id', $id))
            ->filter()
            ->values();

        if ($selectedStaticItems->isEmpty()) {
            return;
        }

        // TODO: Save static menu items to your menu
        // Example: $this->menu->items()->createMany($selectedStaticItems);
    }

    private function processMenuxableSelections(string $modelClass): void
    {
        $data = $this->menuxables[$modelClass] ?? [];

        if (empty($data['items'])) {
            return;
        }

        $validIndices = array_keys($data['items']);

        $selectedMenuxableItems = collect($this->selectedItems)
            ->filter(fn ($selected, $index) => in_array($index, $validIndices))
            ->map(fn ($selected, $index) => $data['items'][$index])
            ->filter()
            ->values();

        if ($selectedMenuxableItems->isEmpty()) {
            return;
        }

        // TODO: Save menuxable menu items to your menu
        // Example: $this->menu->items()->createMany($selectedMenuxableItems);
    }

    public function menuItemFormSchema(Schema $schema): Schema
    {
        return $schema
            ->components(components: [
                Section::make('Menu Items')
                    ->headerActions([
                        Action::make('newItem')
                            ->icon(icon: Heroicon::PlusCircle)
                            ->label('New Custom Menu Item')
                            ->iconButton()
                            ->modalHeading('Add custom menu items directly')
                            ->modalWidth(width: Width::Small)
                            ->schema([
                                TextInput::make('title')
                                    ->required(),
                                TextInput::make('url')
                                    ->required(),
                                Select::make('target')
                                    ->default(MenuItemTarget::SELF)
                                    ->selectablePlaceholder()
                                    ->options(MenuItemTarget::class),
                            ])
                            ->action(function (array $data) {
                                // TODO: Create custom menu item
                                dd($data);
                            }),
                    ])
                    ->compact()
                    ->footerActions([
                        Action::make('addItems')
                            ->label(function () {
                                $count = count($this->selectedItems);
                                if ($count > 0) {
                                    return "Add $count Selected Items";
                                } else {
                                    return 'Add Menu Items';
                                }
                            })
                            ->disabled(fn () => empty($this->selectedItems))
                            ->action(fn () => $this->addMenuItems()),
                    ])
                    ->secondary()
                    ->schema([
                        TextInput::make('search')
                            ->label('Search')
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
        return view('filament-menux::livewire.menu-item-form');
    }
}
