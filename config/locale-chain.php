<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Locale Chain
    |--------------------------------------------------------------------------
    |
    | Set to false to disable the locale chain entirely without removing
    | the service provider.
    |
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Fallback Chains
    |--------------------------------------------------------------------------
    |
    | Custom fallback chains merged with the 75 built-in defaults.
    | Keys you specify replace the corresponding default chain.
    |
    | Example:
    |   'pt-BR' => ['pt-PT', 'pt'],
    |   'es-MX' => ['es-419', 'es'],
    |
    */
    'chains' => [
        // Add your custom chains here
    ],

    /*
    |--------------------------------------------------------------------------
    | Merge Defaults
    |--------------------------------------------------------------------------
    |
    | If true, your custom chains are merged on top of the 75 built-in
    | defaults. Set to false to use ONLY your custom chains.
    |
    */
    'merge_defaults' => true,

];
