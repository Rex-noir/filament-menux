<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux\Livewire;

use AceREx\FilamentMenux\Contracts\Enums\MenuItemTarget;
use AceREx\FilamentMenux\Contracts\Enums\MenuxEvents;
use AceREx\FilamentMenux\Models\MenuItem;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;

class MenuItemsBuilder extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public int $menuId;

    public array $data = [];

    protected $listeners = [
        MenuxEvents::CREATED->value => '$refresh',
    ];

    public function mount(int $menuId): void
    {
        $this->menuId = $menuId;
    }

    public function save(): void
    {
        if (empty($this->data)) {
            return;
        }

        MenuItem::rebuildTree($this->data);

        Notification::make()
            ->title('Menu items saved successfully.')
            ->success()
            ->send();
    }

    public function items(): \LaravelIdea\Helper\AceREx\FilamentMenux\Models\_IH_MenuItem_C | \Illuminate\Database\Eloquent\Collection | array
    {
        $query = MenuItem::query()
            ->where('menu_id', $this->menuId);

        $items = $query->defaultOrder()->get();

        return $items->toTree();
    }

    public function editAction(): Action
    {
        return Action::make('editAction')
            ->size(Size::Small)
            ->icon(Heroicon::PencilSquare)
            ->tooltip('Edit')
            ->schema(function ($arguments) {
                return [
                    TextInput::make('title'),
                    TextInput::make('url'),
                    Select::make('target')
                        ->options(MenuItemTarget::class),
                ];
            })
            ->modalWidth(Width::Medium)
            ->fillForm(function ($arguments) {
                return $arguments;
            })
            ->modalSubmitActionLabel('Save')
            ->action(function ($data, $arguments) {
                MenuItem::where('id', $arguments['id'])->update($data);
            })
            ->iconButton();
    }

    public function render(): \Illuminate\Contracts\View\View | \Illuminate\Contracts\View\Factory | \Illuminate\View\View
    {
        return view(view: 'filament-menux::livewire.menu-items-builder', data: [
            'items' => $this->items(),
        ]);
    }
}
