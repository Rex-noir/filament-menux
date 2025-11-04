<?php

namespace AceREx\FilamentMenux\Filament\Resources\Menus\Schemas;

use AceREx\FilamentMenux\FilamentMenuxPlugin;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class MenuItemForm
{
    public static function make(): array
    {
        $plugin = FilamentMenuxPlugin::get();

        return [
            TextInput::make('title')
                ->required(),
            TextInput::make('url')
                ->required(),
            Select::make('target')
                ->selectablePlaceholder()
                ->required()
                ->options($plugin->getLinkTargetEnum()),
        ];
    }
}
