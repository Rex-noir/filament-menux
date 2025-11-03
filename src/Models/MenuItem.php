<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux\Models;

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
}
