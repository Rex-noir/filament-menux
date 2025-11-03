<?php

namespace AceREx\FilamentMenux\Http\Livewire;

use AceREx\FilamentMenux\Contracts\Enums\MenuItemTarget;
use AceREx\FilamentMenux\FilamentMenuxPlugin;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
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

    protected string $menuId;

    public function mount(string $menuId): void
    {
        $this->menuId = $menuId;
    }

    /**
     * @return array<Illuminate\Support\Traits\TKey,mixed>
     */
    private function getTabs(): array
    {
        $tabs = collect();

        $plugin = FilamentMenuxPlugin::get();
        $staticMenuItems = $plugin->getStaticMenuItems();

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
                    ->schema([
                        Tabs::make('Tabs')
                            ->tabs($this->getTabs())
                            ->vertical()
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
