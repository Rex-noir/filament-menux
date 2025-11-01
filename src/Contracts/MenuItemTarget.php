<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux\Contracts;

use Filament\Support\Contracts\HasLabel;

enum MenuItemTarget: string implements HasLabel
{
    case BLANK = '_blank';
    case SELF = '_self';
    case PARENT = '_parent';
    case TOP = '_top';

    public function getLabel(): string
    {
        return match ($this) {
            self::BLANK => 'Open in new tab',
            self::SELF => 'Open in same tab',
            self::PARENT => 'Open in parent frame',
            self::TOP => 'Open in full window',
        };
    }
}
