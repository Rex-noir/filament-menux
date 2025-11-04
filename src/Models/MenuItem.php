<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux\Models;

use AceREx\FilamentMenux\FilamentMenuxPlugin;
use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

class MenuItem extends Model
{
    use NodeTrait;

    protected $fillable = [
        'menu_id',
        'title',
        'url',
        'order',
        'target',
    ];

    public function casts(): array
    {
        $enum = FilamentMenuxPlugin::get()->getLinkTargetEnum();

        return [
            'target' => $enum,
        ];

    }
}
