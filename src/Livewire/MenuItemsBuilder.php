<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux\Livewire;

use AceREx\FilamentMenux\Contracts\Enums\MenuxEvents;
use AceREx\FilamentMenux\Models\MenuItem;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
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
            ->title(__('menux.notifications.items_saved.title'))
            ->success()
            ->send(); /**/
    }

    public function items(): \LaravelIdea\Helper\AceREx\FilamentMenux\Models\_IH_MenuItem_C | \Illuminate\Database\Eloquent\Collection | array
    {
        $query = MenuItem::query()
            ->where('menu_id', $this->menuId);

        $items = $query->defaultOrder()->get();

        return $items->toTree();
    }

    public function deleteAction(): Action
    {
        return Action::make('deleteAction')
            ->size(Size::Small)
            ->icon(Heroicon::Trash)
            ->color('danger')
            ->tooltip(__('menux.actions.delete'))
            ->requiresConfirmation()
            ->tooltip('Delete')
            ->action(function ($arguments) {
                $id = $arguments['id'];
                MenuItem::descendantsAndSelf($id)->each(function ($item) {
                    $item->delete();
                });
            })
            ->iconButton();
    }

    public function createSubMenuItemAction(): Action
    {
        return Action::make('createSubMenuItemAction')
            ->size(Size::Small)
            ->icon(Heroicon::ChevronDoubleDown)
            ->tooltip(__('menux.actions.add_sub_menu_item'))
            ->modalHeading(__('menux.actions.add_sub_menu_item'))
            ->schema(\AceREx\FilamentMenux\Filament\Resources\Menus\Schemas\MenuItemForm::make())
            ->modalWidth(Width::Medium)
            ->modalSubmitActionLabel(__('menux.actions.save'))
            ->action(function ($data, $arguments) {
                $parent = MenuItem::findOrFail($arguments['id']);
                $item = MenuItem::query()->create(array_merge($data, ['menu_id' => $this->menuId]));
                $parent->appendNode($item);
            })
            ->iconButton();
    }

    public function duplicateAction(): Action
    {
        return Action::make('duplicateAction')
            ->size(Size::Small)
            ->icon(Heroicon::ServerStack)
            ->tooltip(__('menux.actions.duplicate'))
            ->iconButton()
            ->requiresConfirmation()
            ->modalHeading(__('menux.modals.duplicate.title'))
            ->action(function ($arguments) {
                $id = $arguments['id'];
                $item = MenuItem::findOrFail($id);

                // Clone without tree structure columns
                $replica = $item->replicate(['_lft', '_rgt', 'depth']);
                $replica->title = $item->title . ' (Copy)'; // Optional: make duplicate distinct

                // Insert as sibling (same parent, same level)
                $replica->parent_id = $item->parent_id;
                $replica->save();

                // Ensure it becomes a sibling, not child
                $replica->insertAfterNode($item);
            });

    }

    public function editAction(): Action
    {
        return Action::make('editAction')
            ->size(Size::Small)
            ->icon(Heroicon::PencilSquare)
            ->tooltip(__('menux.actions.edit'))
            ->schema(\AceREx\FilamentMenux\Filament\Resources\Menus\Schemas\MenuItemForm::make())
            ->modalWidth(Width::Medium)
            ->fillForm(function ($arguments) {
                return $arguments;
            })
            ->modalSubmitActionLabel(__('menux.actions.save'))
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
