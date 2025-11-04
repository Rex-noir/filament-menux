<?php

namespace AceREx\FilamentMenux\Filament\Resources\Menus;

use AceREx\FilamentMenux\Filament\Resources\Menus\Pages\CreateMenu;
use AceREx\FilamentMenux\Filament\Resources\Menus\Pages\EditMenu;
use AceREx\FilamentMenux\Filament\Resources\Menus\Pages\ListMenus;
use AceREx\FilamentMenux\Filament\Resources\Menus\Schemas\MenuForm;
use AceREx\FilamentMenux\Filament\Resources\Menus\Tables\MenusTable;
use AceREx\FilamentMenux\FilamentMenuxPlugin;
use AceREx\FilamentMenux\Models\Menu;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return FilamentMenuxPlugin::get()->getResourceNavigationGroup();
    }

    public static function form(Schema $schema): Schema
    {
        return MenuForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MenusTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenus::route('/'),
            'create' => CreateMenu::route('/create'),
            'edit' => EditMenu::route('/{record}/edit'),
        ];
    }
}
