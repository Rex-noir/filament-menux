<?php

namespace AceREx\FilamentMenux\Filament\Resources\Menus\Schemas;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class MenuForm
{
    public static function configure(): array
    {
        return [
            Section::make('Menu')
                ->collapsible()
                ->headerActions([
                    DeleteAction::make(),
                    Action::make('save')
                        ->label('Save')
                        ->button()
                        ->action('save'),
                ])
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),
                ]),
        ];
    }
}
