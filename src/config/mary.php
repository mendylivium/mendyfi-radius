<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Prefix
    |--------------------------------------------------------------------------
    |
    | By default, all components are registered with the "mary-" prefix.
    | e.g. <x-mary-button />, <x-mary-stat />, <x-mary-card />
    |
    */
    'prefix' => 'mary-',

    /*
    |--------------------------------------------------------------------------
    | Icons
    |--------------------------------------------------------------------------
    |
    | Mary uses Blade Icons package under the hood.
    | You must install the icon set you want to use.
    | See https://blade-ui-kit.com/blade-icons
    |
    | Recommended: composer require blade-ui-kit/blade-heroicons
    |
    */
    'icons' => [
        // Default icon set
        'heroicons' => 'heroicons',
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    |
    | Mary UI requires Livewire 3+
    |
    */
    'livewire' => [],
];
