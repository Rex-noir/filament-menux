<?php

namespace AceREx\FilamentMenux\Filament\Resources\Menus\Schemas;

use AceREx\FilamentMenux\Contracts\Enums\MenuItemTarget;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class MenuItemForm
{
    public static function make(): array
    {
        return [
            TextInput::make('title')
                ->required(),
            TextInput::make('url')
                ->required(),
            Select::make('target')
                ->default(MenuItemTarget::SELF)
                ->selectablePlaceholder()
                ->options(MenuItemTarget::class),
        ];
    }
}
