<div class="item" data-id="{{ $item->id }}" wire:key="{{'menu-item-'.$item->id}}">
    <div @class([
        'flex justify-between mb-2 content-center rounded bg-white border border-gray-300 shadow-sm pr-2 dark:bg-gray-900 dark:border-gray-800' => true
])>
        <div class="flex content-center items-center">
            <div class="border-r-2 border-gray-300 dark:border-gray-800 cursor-pointer bg-grey-400">
                <x-heroicon-o-arrows-up-down class="w-6 h-6 m-2 handle" />
            </div>
            <div class="ml-2 flex gap-x-3">
                <span class="font-medium">{{ str($item->title)->limit(30) }}</span>
                <div>
                    <x-filament::link
                        tag="button"
                        weight="light"
                        size="sm"
                    >
                        <a target="_blank" href="{{$item->url}}">
                            {{$item->url}}
                        </a>
                    </x-filament::link>
                </div>
            </div>
        </div>
        <div class="flex gap-2 items-center [&_svg]:shrink-0">
            {{ ($this->editAction)(['title'=>$item->title, 'url'=>$item->url, 'target'=>$item->target, 'id'=>$item->id])  }}
            {{--            {{($this->createSubItemAction)(['menuItemId' => $item->id])}}--}}
            {{--            {{($this->duplicateAction)(['menuItemId' => $item->id])}}--}}
            {{($this->deleteAction)(['id' => $item->id])}}
            {{--            <x-filament-actions::group class="hidden" :actions="[--}}
            {{--                ($this->viewAction)(['menuItemId' => $item->id]),--}}
            {{--                ($this->goToLinkAction)([])->url($item->is_link_resolved ? $item->link : '#'),--}}
            {{--            ]" />--}}
        </div>
    </div>

    <div
        @class(['nested ml-6' => true])
        data-id="{{ $item->id }}"
        x-data="{
            init(){
                new Sortable(this.$el, {
                    handle: '.handle',
                    group: 'nested',
                    animation: 150,
                    fallbackOnBody: true,
                    swapThreshold: 0.65,
                    onEnd: (evt) => {
                        this.data = getDataStructure(document.getElementById('parentNested'));
                    }
                })
            },
        }"
    >
        @foreach($item->children as $children)
            @include('filament-menux::components.menu-item', ['item'=>$children])
        @endforeach
    </div>
</div>
