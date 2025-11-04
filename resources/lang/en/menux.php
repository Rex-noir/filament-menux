<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Filament Menux Translations
    |--------------------------------------------------------------------------
    |
    | These strings are used across the Filament Menux plugin interface.
    | You can freely modify them to match your application's tone and language.
    |
    */

    'tabs' => [
        'static' => 'Static',
        'no_items_found' => 'No items found matching your search query.',
        'no_items_for_model' => 'No items found for :label',
        'page_of' => 'Page :current of :last',
        'load_previous' => 'Load Previous',
        'load_more' => 'Load More',
    ],

    'actions' => [
        'duplicate' => 'Duplicate',
        'new_item' => 'New Custom Menu Item',
        'add_items' => 'Add Menu Items',
        'add_item' => 'Add New Item',
        'add_selected' => 'Add :count Selected Items',
        'load_previous' => 'Load Previous',
        'delete' => 'Delete',
        'delete_selected' => 'Delete :count Selected Items',
        'add_sub_menu_item' => 'Add Sub Menu Item',
        'edit' => 'Edit',
        'save' => 'Save',
        'load_more' => 'Load More',
        'select_all' => 'Select All',
        'select' => 'Select',
    ],

    'modals' => [
        'duplicate' => [
            'title' => 'Duplicate Menu Item?',
        ],
    ],

    'notifications' => [
        'menu_item_created' => [
            'title' => 'Menu item created successfully',
            'body' => null,
        ],
        'items_added' => [
            'title' => 'Menu items added successfully',
            'body' => 'Total items added: :count',
        ],
        'items_saved' => [
            'title' => 'Menu items saved successfully',
        ],
        'menu_items_deleted' => [
            'title' => 'Menu items deleted successfully',
            'body' => 'Total items deleted: :count',
        ],
    ],

    'placeholders' => [
        'search' => 'Search menu items...',
    ],

    'labels' => [
        'search' => 'Search',
        'menu_items' => 'Menu Items',
        'custom_menu_item_modal_heading' => 'Add custom menu items directly',
        'menu_items_builder_heading' => 'Menu Items Builder',
        'menu_items_delete_selected_action_heading' => 'Delete selected menu items? Total : :count',
    ],

    'empty_state' => [
        'icon' => 'heroicon-o-exclamation-circle',
        'description' => 'No items found.',
    ],

];
