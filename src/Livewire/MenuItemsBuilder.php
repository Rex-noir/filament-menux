<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux\Livewire;

use AceREx\FilamentMenux\Contracts\Enums\MenuxEvents;
use AceREx\FilamentMenux\Models\MenuItem;
use Filament\Notifications\Notification;
use Livewire\Component;

class MenuItemsBuilder extends Component
{
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
            ->title(__('filament-menu-builder::menu-builder.menu_saved'))
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

    public function render(): \Illuminate\Contracts\View\View | \Illuminate\Contracts\View\Factory | \Illuminate\View\View
    {
        return view(view: 'filament-menux::livewire.menu-items-builder', data: [
            'items' => $this->items(),
        ]);
    }
}
