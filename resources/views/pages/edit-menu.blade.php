<x-filament-panels::page>
    {{$this->form}}
    <div>
        @livewire('menu-items-builder', ['menuId'=>$this->record->id]))
    </div>
</x-filament-panels::page>
