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

    private function buildMenuxableData(string $modelClass, int $page = 1): void
    {
        $plugin = FilamentMenuxPlugin::get();
        $perPage = $plugin->getMenuxablesPerPage();
        /** @var Menuxable $modelClass */
        $pagination = $modelClass::getMenuxablesUsing($this->searchQuery, $modelClass::query())->paginate($perPage, page: $page);
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
                            $pagination[] = Action::make('loadPrevious' . md5($modelClass))
                                ->label(__('menux.actions.load_previous'))
                                ->icon(icon: Heroicon::ChevronLeft)
                                ->link()
                                ->iconButton()
                                ->disabled($data['current_page'] <= 1)
                                ->action(fn () => $this->goToPage($modelClass, $data['current_page'] - 1));

                            $pagination[] = Text::make(__('menux.tabs.page_of', ['current' => $data['current_page'], 'last' => $data['last_page']]));

                            /** @var string $modelClass */
                            $pagination[] = Action::make('loadMore' . md5($modelClass))
                                ->label(__('menux.actions.load_more'))
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
                                $components[] = EmptyState::make(__('menux.tabs.no_items_for_model', ['label' => $modelClass::getMenuxLabel()]))
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
        /** @var MenuItem $itemModel */
        $itemModel = FilamentMenuxPlugin::get()->getMenuItemModel();
        $plugin = FilamentMenuxPlugin::get();
        /** @var BackedEnum&HasStaticDefaultValue $enum */
        $enum = $plugin->getLinkTargetEnum();

        $items = collect($this->staticItems);
        collect($this->menuxables)->each(function ($data, $modelClass) use (&$items) {
            $items = $items->merge(collect($data['items'])->mapWithKeys(fn ($item, $index) => [$item['id'] => $item]));
        });

        $itemsToAdd = collect($this->selectedItems)->mapWithKeys(function ($item, $index) use ($items, $enum) {
            $itemData = $items->get($item);
            $itemData['target'] = $enum::tryFrom($itemData['target']) ?? $enum::getStaticDefaultValue();
            $itemData['menu_id'] = $this->menuId;
            unset($itemData['id']);
            unset($itemData['type']);

            return [$item => $itemData];
        });

        if (empty($itemsToAdd)) {
            return;
        }
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
}
