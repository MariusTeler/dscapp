<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AWB Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for AWB (Air Waybill).
    | This includes various settings related to AWB processing.
    |
    */

    'tip_obj' => [
        1 => 'Plic',
        2 => 'Colet',
        3 => 'Palet'
    ],

    'tip_exp' => [
        0 => 'Initiala',
        1 => 'Retur NT',
        2 => 'Retur Doc.',
        3 => 'Ramburs',
        4 => 'Interna',
        5 => 'Returnare',
        6 => 'Retur ambalaj',
        7 => 'Retur colet',
        33 => 'Borderou RBS cash'
    ],
    //RBS_TIP_PLATA : 0=>'cash', 1=>'bo', 2=>'cec', 3=>'cont'
    'rbs_tip_plata' => [
        0 => 'cash',
        1 => 'bo',
        2 => 'cec',
        3 => 'cont colector'
    ],

    //0=>'Per NT', 1=>'Factura periodica', 3=>'barter'
    'mod_plata' => [
        0 => 'Per NT',
        1 => 'Factura periodica',
        2 => 'Barter',
    ],

    //MONEDA = [1=>'LEI' ,2=>'EUR' ,3=>'USD'];
    'moneda' => [
        1 => 'RON',
        2 => 'EUR',
        3 => 'USD'
    ],

    'limits' => [
        'min_greutate_colet' => 1,
        'max_greutate_colet' => 9999,
        'min_greutate_palet' => 10.0,
        'max_greutate_palet' => 9999,
        'min_piese_colet' => 1,
        'max_piese_colet' => 999,
        'max_asigurare' => 15000.0,
        'max_ramburs' => 15000.0,
        'import' => [
            'awb' => [
                'csv' => [
                    'max_rows' => 1000,
                ],
                'xls' => [
                    'max_rows' => 1000,
                ],
                'maravet' => [
                    'max_rows' => 1000,
                ],
            ],
            'destinatari' => [
                'csv' => [
                    'max_rows' => 1000,
                ],
            ],
        ],
    ],

    //const DEFAULT_KG_RET_AMB = 3.00;
    'default_kg_ret_amb' => 3.00,
    //const DEFAULT_TARIF_OPEN = 20.00;
    'default_tarif_open' => 20.00,

    'can_update_after_print_masters' => [
        //list of master IDs that can update AWB after printing
        171350, 435076, 1631955, 1631972, 3361158
    ],

    'can_import_xls' => [171350, 435076],

    'import_headers' => [
        'csv' => [
            'required' => [
                'destinatar',
                'judet',
                'localitate',
                'adresa',
                'tip',
                'piese',
                'greutate', 
            ],
            'optional' => [
                'platitor',
                'contact',
                'telefon',
                'email',
                'valoare_declarata',
                'ramburs',
                'tip_plata',
                'retur_nt',
                'retur_documente',
                'retur_ambalaj',
                'retur_colet',
                'sms_livrare',
                'deschidere_colet',
                'livrare_sambata',
                'livrare_sediu',
                'observatii',
                'detalii_documente',
            ],
            'max_rows' => 1000,
        ],
        'xls' => [
            'maravet' => [
                'required' => [
                    'codbara','plic','colet','palet','greutate','clientdest','adresadest','orasdest','judetdest','centru','perscontactdest','telefondest','observatii','serieclient','rambursnumerar','ramburscontcolector','rambursalttip','platitorexpeditie','livraresambata','email','frig','continut','valoaredeclarata','extrainfo','largeinfo'
                ],
                'optional' => [
                    'codpostaldest','intervallivrare','deschiderecolet','taradest','emaildest','disclaimer','refexp1','refdest1','refdest2','referintafacturare'
                ],
            ],
            'max_rows' => 1000,
        ],
    ],

    'import_destinatari_headers' => [
        'csv' => [
            'required' => [
                'nume',
                'judet',
                'localitate',
                'adresa',
                'contact',
                'telefon',
                'email',
            ],
            'optional' => [],
            'max_rows' => 500,
        ],
    ],

    'regexp' => [
        'awb' => [
            'system' => '/^[789][0-9]{7}$/',
            'puisor' => '/^([89][0-9]{7}|(290|291)[0-9]{6}|3[0-9]{9})-[0-9]{1,3}$/',
            'android' => '/^3[0-9]{9}$/',
            'cmn' => '/^1000[0-9]{5}$/',
            'old' => '/^[1-9][0-9]{6}$/',
            'rosie' => '/^1[0-9]{8}$/',
            'maravet' => '/^(290|291)[0-9]{6}$/',
        ],
        'telefon' => '/^(\+?40\s?|0)?7[0-9]\d{7}$/',
    ],
];