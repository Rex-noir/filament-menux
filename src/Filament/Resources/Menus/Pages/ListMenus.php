<?php

namespace AceREx\FilamentMenux\Filament\Resources\Menus\Pages;

use AceREx\FilamentMenux\Filament\Resources\Menus\MenuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMenus extends ListRecords
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
