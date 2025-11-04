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

    public array $selectedItems = [];

    protected $listeners = [
        MenuxEvents::CREATED->value => '$refresh',
    ];

    public function getAllSelectedProperty(): bool
    {
        $allIds = $this->items()->toFlatTree()->pluck('id')
            ->toArray();

        return ! empty($allIds) && count($this->selectedItems) === count($allIds);

    }

    public function isSelected(int $id): bool
    {
        return in_array($id, $this->selectedItems, true);
    }

    public function toggleSelectAll(bool $checked): void
    {
        $allIds = $this->items()->flatMap(function (MenuItem $item) {
            // Collect this item's ID and all its descendant IDs
            return collect([$item->id])
                ->merge($item->descendants()->pluck('id'));
        })->toArray();

        $this->selectedItems = $checked ? $allIds : [];
    }

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
            ->with('children')
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

    public function deleteSelectedAction(): Action
    {
        return Action::make('deleteSelectedAction')
            ->size(Size::Small)
            ->tooltip(function () {
                $selected = count($this->selectedItems);

                return __('menux.actions.delete_selected', ['count' => $selected]) . ' (' . implode(', ', $this->selectedItems) . ')';
            })
            ->icon(Heroicon::Trash)
            ->disabled($this->selectedItems === [])
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('menux.labels.menu_items_delete_selected_action_heading', ['count' => count($this->selectedItems)]))
            ->tooltip('Delete')
            ->action(function ($arguments) {
                MenuItem::whereIn('id', $this->selectedItems)->delete();
                Notification::make('menuItemsDeleted')
                    ->title(__('menux.notifications.menu_items_deleted.title'))
                    ->body(__('menux.notifications.menu_items_deleted.body', ['count' => count($this->selectedItems)]))
                    ->success()
                    ->send();
                $this->selectedItems = [];
            })
            ->iconButton();
    }

    public function addCustomAction(): Action
    {
        return Action::make('newCustomMenuItem')
            ->icon(icon: Heroicon::PlusCircle)
            ->label(__('menux.actions.add_item'))
            ->size(Size::Small)
            ->tooltip(__('menux.actions.add_item'))
            ->modalHeading(__('menux.labels.custom_menu_item_modal_heading'))
            ->modalWidth(width: Width::Small)
            ->modalSubmitActionLabel(__('menux.actions.save'))
            ->schema(\AceREx\FilamentMenux\Filament\Resources\Menus\Schemas\MenuItemForm::make())
            ->action(function ($data) {
                MenuItem::query()->create(array_merge($data, ['menu_id' => $this->menuId]));
                Notification::make('menuItemCreated')
                    ->success()
                    ->title(__('menux.notifications.menu_item_created.title'))
                    ->send();
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
