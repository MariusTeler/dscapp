<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TVA Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for TVA (Taxa pe Valoarea Adaugata).
    | This includes the standard rate, reduced rates, and any exemptions.
    |
    */
    'proc_tva_standard' => 21,

    'proc_tva' => [
        '2013-01-01' => 24,
        '2016-01-01' => 20,
        '2017-01-01' => 19,
        '2025-08-01' => 21,
    ]
];