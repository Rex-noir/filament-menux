<x-filament-panels::page>
    {{$this->form}}
    <div class="grid grid-cols-5">
        <div class="col-span-2">
            @livewire('menu-item-form', ['menuId' => $this->record->id])
        </div>
        <div>
            @livewire('menu-items-builder', ['menuId'=>$this->record->id])
        </div>
    </div>
</x-filament-panels::page>
