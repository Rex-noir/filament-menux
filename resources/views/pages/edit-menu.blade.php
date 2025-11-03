<x-filament-panels::page>
    {{$this->form}}
    <div class="space-y-4 md:grid grid-cols-5 gap-5">
        <div class="col-span-2">
            @livewire('menu-item-tabs', ['menuId' => $this->record->id])
        </div>
        <div class="col-span-3">
            @livewire('menu-items-builder', ['menuId'=>$this->record->id])
        </div>
    </div>
</x-filament-panels::page>
