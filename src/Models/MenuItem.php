<?php

declare(strict_types=1);

namespace AceREx\FilamentMenux\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id',
        'title',
        'url',
        'order',
        'target'
    ];
}
