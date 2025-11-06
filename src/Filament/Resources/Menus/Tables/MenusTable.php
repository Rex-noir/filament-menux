<?php

namespace AceREx\FilamentMenux\Filament\Resources\Menus\Tables;

use AceREx\FilamentMenux\Contracts\Enums\MenuxActionType;
use AceREx\FilamentMenux\Contracts\Traits\HasActionModifier;
use AceREx\FilamentMenux\FilamentMenuxPlugin;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MenusTable
{
    use HasActionModifier;

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                self::applyActionModifier(EditAction::make(), MenuxActionType::EDIT_MENU)
                    ->visible(function () {
                        $plugin = FilamentMenuxPlugin::get();

                        return $plugin->getStaticMenus()->isEmpty();
                    }),
                self::applyActionModifier(DeleteAction::make(), MenuxActionType::DELETE_MENU)
                    ->visible(function () {
                        $plugin = FilamentMenuxPlugin::get();

                        return $plugin->getStaticMenus()->isEmpty();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
