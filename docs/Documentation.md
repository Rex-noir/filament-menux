# Filament Menux Documentation

## Table of Contents

- [Introduction](#introduction)
- [Registering to panel](#registering-to-panel)
- [Static Menus](#static-menus)
- [Configuration](#configuration)

## Introduction

Inspired by existing menu builders, but simplified and easier to customize.

## Registering to panel

Just like any other panel plugins, you can register this in your panel provider
```php
->plugins([
    \AceREx\FilamentMenux\FilamentMenuxPlugin::make()
])
```

## Static Menus

With static menus you can limit how many menus can be created except the menus you provided.
This is useful, especially for projects where the frontend fetch the menus statically via slug.
To pass static menus,

```php
\AceREx\FilamentMenux\FilamentMenuxPlugin::make()
    ->useStaticMenus([
        'slug'=>'label',
        'header'=>"Header"
    ])
```
