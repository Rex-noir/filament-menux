<?php

namespace AceREx\FilamentMenux\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;

class Menu extends Model
{
    use HasSlug;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function getSlugOptions(): \Spatie\Sluggable\SlugOptions
    {
        return \Spatie\Sluggable\SlugOptions::create()
            ->generateSlugsFrom('name')
            ->doNotGenerateSlugsOnUpdate()
            ->saveSlugsTo('slug');
    }
}
