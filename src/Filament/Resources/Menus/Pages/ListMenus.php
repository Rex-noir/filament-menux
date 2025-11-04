<?php

namespace AceREx\FilamentMenux\Filament\Resources\Menus\Pages;

use AceREx\FilamentMenux\Filament\Resources\Menus\MenuResource;
use AceREx\FilamentMenux\FilamentMenuxPlugin;
use AceREx\FilamentMenux\Models\Menu;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListMenus extends ListRecords
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        $plugin = FilamentMenuxPlugin::get();
        $staticMenus = $plugin->getStaticMenus();
        $existingSlugs = Menu::whereIn('slug', $staticMenus->keys())->pluck('slug');

        if ($staticMenus->count() === $existingSlugs->count()) {
            return [];
        }

        if ($staticMenus->isNotEmpty()) {

            return [
                Action::make('create')
                    ->label('Add Menu')
                    ->schema([
                        CheckboxList::make('selected_menu_items')
                            ->label('Select menus to create')
                            ->options($staticMenus->toArray())
                            ->disableOptionWhen(fn ($value, $key): bool => in_array($value, $existingSlugs->toArray()))
                            ->helperText('Menus that already exist are disabled.')
                            ->required(),
                    ])
                    ->action(function (array $data) use ($staticMenus) {
                        foreach ($data['selected_menu_items'] as $slug) {
                            Menu::firstOrCreate([
                                'slug' => $slug,
                            ], [
                                'name' => $staticMenus->get($slug),
                            ]);
                        }
                        Notification::make()
                            ->title('Menus created successfully.')
                            ->success()
                            ->send();
                    }),
            ];
        }

        return [
            CreateAction::make(),
        ];
    }
}
