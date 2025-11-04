<?php

namespace AceREx\FilamentMenux\Contracts\Interfaces;

use Filament\Actions\Action;

interface ActionModifier
{
    public function modify(Action $action): Action;
}
