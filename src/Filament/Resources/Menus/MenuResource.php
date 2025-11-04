<?php

namespace AceREx\FilamentMenux\Filament\Resources\Menus;

use AceREx\FilamentMenux\Filament\Resources\Menus\Pages\CreateMenu;
use AceREx\FilamentMenux\Filament\Resources\Menus\Pages\EditMenu;
use AceREx\FilamentMenux\Filament\Resources\Menus\Pages\ListMenus;
use AceREx\FilamentMenux\Filament\Resources\Menus\Tables\MenusTable;
use AceREx\FilamentMenux\FilamentMenuxPlugin;
use AceREx\FilamentMenux\Models\Menu;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModel(): string
    {
        return FilamentMenuxPlugin::get()->getMenuModel();
    }

    public static function getNavigationLabel(): string
    {
        return FilamentMenuxPlugin::get()->getNavigationLabel();
    }

    public static function getNavigationIcon(): string | BackedEnum | Htmlable | null
    {
        return FilamentMenuxPlugin::get()->getNavigationIcon() ?? Heroicon::OutlinedRectangleStack;
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return FilamentMenuxPlugin::get()->getResourceNavigationGroup();
    }

    public static function table(Table $table): Table
    {
        /** @var MenusTable $menusTable */
        $menusTable = FilamentMenuxPlugin::get()->getMenusTable();

        return $menusTable::configure($table);
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
