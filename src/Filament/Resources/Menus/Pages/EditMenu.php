<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux\Filament\Resources\Menus\Pages;

use AceREx\FilamentMenux\Filament\Resources\Menus\MenuResource;
use AceREx\FilamentMenux\Models\Menu;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditMenu extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithRecord;

    public ?array $data = [];

    protected static string $resource = MenuResource::class;

    protected string $view = 'filament-menux::pages.edit-menu';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Menu')
                    ->collapsible()
                    ->headerActions([
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
            ])
            ->model(Menu::class)
            ->statePath('data');
    }

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->form->fill($this->record->toArray());
    }

    // Add a save method
    public function save(): void
    {
        $data = $this->form->getState();
        $this->record->update($data);
        Notification::make()
            ->title('Menu updated successfully')
            ->success()
            ->send();
    }
}
