<?php

namespace AceREx\FilamentMenux\Contracts\Traits;

use AceREx\FilamentMenux\Contracts\Enums\MenuxActionType;
use AceREx\FilamentMenux\FilamentMenuxPlugin;
use Filament\Actions\Action;

trait HasActionModifier
{
    protected function applyActionModifier(Action $action, MenuxActionType $actionType): \Filament\Actions\Action
    {
        $plugin = FilamentMenuxPlugin::get();
        if ($plugin->hasActionModifier($actionType)) {
            $modifier = $plugin->getActionModifier($actionType);

            return $modifier->modify($action);
        }

        return $action;
    }
}
