<?php

namespace AceREx\FilamentMenux\Livewire;

use AceREx\FilamentMenux\Models\MenuItem;
use Livewire\Component;

class MenuItemUi extends Component
{
    public MenuItem $item;

    public function mount(MenuItem $item): void
    {
        $this->item = $item;
    }

    public function render(): \Illuminate\View\View
    {
        return view('filament-menux::livewire.menu-item-ui', data: []);
    }
}
