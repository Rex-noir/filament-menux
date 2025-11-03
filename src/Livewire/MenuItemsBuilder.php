<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux\Livewire;

use Livewire\Component;

class MenuItemsBuilder extends Component
{
    public function render(): \Illuminate\Contracts\View\View | \Illuminate\Contracts\View\Factory | \Illuminate\View\View
    {
        return view('filament-menux::livewire.menu-items-builder');
    }
}
