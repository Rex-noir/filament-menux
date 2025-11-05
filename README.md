# Good or bad, you decide.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/acerex/filament-menux.svg?style=flat-square)](https://packagist.org/packages/acerex/filament-menux)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/acerex/filament-menux/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/acerex/filament-menux/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/acerex/filament-menux/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/acerex/filament-menux/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/acerex/filament-menux.svg?style=flat-square)](https://packagist.org/packages/acerex/filament-menux)


Inspired by existing menu builders, but simplified and easier to customize.


## Table of Contents

- [Installation](#installation)
- [Registering to panel](#registering-to-panel)
- [Static Menus](#static-menus)
- [Static Menu Items](#static-menu-items)


## Installation

You can install the package via composer:

```bash
composer require acerex/filament-menux
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-menux-migrations"
php artisan migrate
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filament-menux-views"
```

## Usage

To start using, add the plugin to the panel you want.
```php
FilamentMenuxPlugin::make()
    ->useStaticMenus([
        'header' => 'Header',
        'footer' => 'Footer',
        ])
    ->addStaticMenuItem('Home', '/')
    ->setNavigationLabel('WATASHI')
    ->setPerPage(4)
    ->setActionModifierUsing(MenuxActionType::EDIT_MENU_ITEM, function (Action $action) {
        return $action->icon(Heroicon::MagnifyingGlassCircle);
    })
    ->addMenuxableModel(Post::class)
    ->setResourceNavigationGroup('WATASHI')
    ->addStaticMenuItem('Contact Us', '/contact-us')
    ->setLinkTargetEnum(linkTargetEnum: LinkTarget::class)
    ->addMenuxableModel(model: Page::class),

```



## Registering to panel

Just like any other panel plugins, you can register this in your panel provider

```php
->plugins([
    \AceREx\FilamentMenux\FilamentMenuxPlugin::make()
])
```

## Static Menus

With static menus you can limit how many menus can be created except the menus you provided.
This is useful, especially for projects where the frontend fetches the menus statically via slug.
To pass static menus you pass the menus to the **useStaticMenus** method.

```php
\AceREx\FilamentMenux\FilamentMenuxPlugin::make()
    ->useStaticMenus([
        'slug'=>'label',
        'header'=>"Header"
    ])
```

![Static Menu Items Create](docs/images/static-menus-create-dialog.png)

## Static Menu Items

Static menu items are shown menu items that you provide from the panel configuration.
You can add static menu items like this.

```php
->addStaticMenuItem('Home', '/', '_self')

```

The third argument is optional and can also be any type of backed enum. For consistency, you should
use the enum you use for the item form. See [Using custom link target enum](#using-custom-link-target-enum)


## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
