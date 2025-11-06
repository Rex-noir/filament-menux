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

    protected $timestamps = false;

    public function casts(): array
    {
        $enum = FilamentMenuxPlugin::get()->getLinkTargetEnum();

        return [
            'target' => $enum,
        ];
    }

    public function childrenRecursive(): \Illuminate\Database\Eloquent\Builder | \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    public function isActive(): bool
    {
        $currentUrl = \Request::url();
        $itemUrl = $this->url;

        if ($itemUrl) {
            // Skip external URLs (different host)
            $currentHost = parse_url($currentUrl, PHP_URL_HOST);
            $itemHost = parse_url($itemUrl, PHP_URL_HOST);

            // If link is external, never mark it active
            if ($itemHost && $itemHost !== $currentHost) {
                return false;
            }

            // Remove trailing slashes for comparison
            $currentPath = rtrim(parse_url($currentUrl, PHP_URL_PATH) ?? '', '/');
            $itemPath = rtrim(parse_url($itemUrl, PHP_URL_PATH) ?? '', '/');

            if ($itemUrl === '#') {
                return false;
            }

            // Special case: both are empty (root paths)
            if ($currentPath === '' && $itemPath === '') {
                return true;
            }

            // Special case: if item is root path, only match exact root
            if ($itemPath === '' || $itemPath === '/') {
                return $currentPath === '' || $currentPath === '/';
            }

            // For non-root paths, use starts_with logic
            if ($currentPath === $itemPath || str_starts_with($currentPath, $itemPath . '/')) {
                return true;
            }
        }

        // Check children
        foreach ($this->children as $child) {
            if ($child->isActive()) {
                return true;
            }
        }

        return false;
    }
}
