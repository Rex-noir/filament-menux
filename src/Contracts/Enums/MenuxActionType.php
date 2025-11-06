<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux\Contracts\Enums;

use AceREx\FilamentMenux\Filament\Resources\Menus\Pages\ListMenus;
use AceREx\FilamentMenux\Filament\Resources\Menus\Tables\MenusTable;

enum MenuxActionType: string
{
    /**
     * Used in menu items builder inside the action group.
     */
    case DELETE_MENU_ITEM = 'delete-item';

    /**
     *Used in the menu items builder to delete the selected items.
     */
    case DELETE_SELECTED_MENU_ITEMS = 'delete-selected-items';

    /**
     * Used in menu items builder inside the action group.
     */
    case DUPLICATE__MENU_ITEM = 'duplicate-item';
    /**
     * Used in the menu items builder to edit created menu item.
     */
    case EDIT_MENU_ITEM = 'edit-item';

    /**
     * Used for adding a menu item not defined in the menu tabs.
     */
    case ADD_CUSTOM_MENU_ITEM = 'add-custom-item';

    /**
     * Used for adding a sub menu-item right under the item.
     */
    case CREATE_SUB_MENU_ITEM = 'create-sub-menu-item';
    /**
     * Used for creating the menu from the {@see ListMenus} or the resource header.
     */
    case CREATE_MENU = 'create-menu';
    /**
     * Used inside {@see MenusTable} actions. If you use your own Menus Table, then it might be more practical to modify it inside that custom table.
     */
    case DELETE_MENU = 'delete-menu';
    /**
     * Used inside {@see MenusTable} actions. If you use your own Menus Table, then it might be more practical to modify it inside that custom table.
     */
    case EDIT_MENU = 'edit-menu';
}
