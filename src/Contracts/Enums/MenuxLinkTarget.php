<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux\Contracts\Enums;

use AceREx\FilamentMenux\Contracts\Interfaces\HasStaticDefaultValue;
use Filament\Support\Contracts\HasLabel;

enum MenuxLinkTarget: string implements HasLabel, HasStaticDefaultValue
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

    public static function getStaticDefaultValue(): HasStaticDefaultValue
    {
        return self::SELF;
    }
}
