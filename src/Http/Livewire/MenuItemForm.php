<?php

namespace AceREx\FilamentMenux\Http\Livewire;

class MenuItemForm extends \Livewire\Component
{
    protected string $menuId;

    public function mount(string $menuId)
    {
        $this->menuId = $menuId;
    }

    public function render()
    {
        return view('filament-menux::livewire.menu-item-form');
    }
}
