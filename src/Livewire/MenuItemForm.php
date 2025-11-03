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

class MenuItemForm extends \Livewire\Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public string $menuId;

    public array $menuxables = [];

    public ?string $searchQuery = null;

    public function mount(string $menuId): void
    {
        $this->menuId = $menuId;
        $this->loadMenuxables();
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

    private function getTabs(): array
    {
        $tabs = collect();

        $plugin = FilamentMenuxPlugin::get();
        $staticMenuItems = $plugin->getStaticMenuItems();
        $menuxableModels = $plugin->getMenuxableModels();

        if ($staticMenuItems->isNotEmpty()) {
            $tabs->push(
                Tab::make('Static')
                    ->schema(function () use ($staticMenuItems) {
                        return [
                            CheckboxList::make('static_menu_items')
                                ->hiddenLabel()
                                ->options($staticMenuItems->mapWithKeys(function ($item, $id) {
                                    return [$id => $item['label']];
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
                        ->schema(function () use ($modelClass) {
                            /** @var string $modelClass */
                            $data = $this->menuxables[$modelClass] ?? [];
                            $pagination = [];
                            $pagination[] = Action::make('loadPrevious')
                                ->label('Load Previous')
                                ->icon(icon: Heroicon::ChevronLeft)
                                ->link()
                                ->iconButton()
                                ->disabled($data['current_page'] <= 1)
                                ->action(function () use ($modelClass, $data) {
                                    $this->buildMenuxableData($modelClass, $data['current_page'] - 1);
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
                                    $this->buildMenuxableData($modelClass, $data['current_page'] + 1);
                                });

                            $components = [
                                CheckboxList::make("{$modelClass}.items")
                                    ->hiddenLabel()
                                    ->options(function () use ($modelClass) {
                                        $data = $this->menuxables[$modelClass] ?? ['items' => []];

                                        return collect($data['items'])->mapWithKeys(fn ($item) => [$item['url'] => $item['title']])->toArray();
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
                                dd($data);
                            }),
                    ])
                    ->compact()
                    ->footerActions([
                        Action::make('Add'),
                    ])
                    ->secondary()
                    ->schema([
                        Tabs::make('Tabs')
                            ->tabs($this->getTabs())
                            ->persistTab()
                            ->contained(),
                    ]),
            ]);
    }

    public function render(): View
    {
        return view('filament-menux::livewire.menu-item-form');
    }
}
