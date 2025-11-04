<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux\Contracts\Enums;

enum MenuxActionType: string
{
    case DELETE_MENU_ITEM = 'delete-item';
    case DELETE_SELECTED_MENU_ITEMS = 'delete-selected-items';
    case DUPLICATE__MENU_ITEM = 'duplicate-item';
    case EDIT_MENU_ITEM = 'edit-item';
    case ADD_CUSTOM_MENU_ITEM = 'add-custom-item';
    case CREATE_SUB_MENU_ITEM = 'create-sub-menu-item';
    case CREATE_MENU = 'create-menu';
    case DELETE_MENU = 'delete-menu';
    case EDIT_MENU = 'edit-menu';
}
