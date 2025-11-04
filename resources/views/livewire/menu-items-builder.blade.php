<div>
    <x-filament::section compact>
        <x-slot name="heading">
            {{__('menux.labels.menu_items_builder_heading')}}
        </x-slot>
        <form wire:submit="save" x-data="{
        data: $wire.entangle('data'),
        sortables: [],
        getDataStructure(parentNode) {
          const items = Array.from(parentNode.children).filter((item) => {
            return item.classList.contains('item');
          }); // Get children items of the current node

          return Array.from(items).map((item) => {
            const id = item.getAttribute('data-id');
            const nestedContainer = item.querySelector('.nested');
            const children = nestedContainer ? this.getDataStructure(nestedContainer): [];

            return { id: parseInt(id), children };
          });
        }
    }"
        >
            @if($items->count() > 0)
                <div class="nested-wrapper">
                    <div id="parentNested" class="nested"
                         x-data="{
                    init(){
                        new Sortable(this.$el, {
                            handle: '.handle',
                            group: 'nested',
                            animation: 150,
                            fallbackOnBody: true,
                            swapThreshold: 0.65,
                            onEnd: (evt) => {
                               const newData = this.getDataStructure(document.getElementById('parentNested'));
                               const oldData = this.data;

                               if (JSON.stringify(newData) !== JSON.stringify(oldData)) {
                                    this.data = newData;
                                    this.$wire.save();
                                }
                            }
                        })
                    },
                }">
                        @foreach($items as $item)
                            @include('filament-menux::components.menu-item',  ['item' => $item])
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-gray-500 text-center">
                    <p>
                        {{ __('filament-menu-builder::menu-builder.empty_menu_items_hint_1') }}
                    </p>
                    <p>
                        {{ __('filament-menu-builder::menu-builder.empty_menu_items_hint_2') }}
                    </p>
                </div>
            @endif
        </form>

    </x-filament::section>
    <x-filament-actions::modals />
</div>
