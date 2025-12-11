<?php

return [

    'show_warnings' => false,

    'convert_entities' => true,

    'public_path' => null,

    'options' => [
        'isPhpEnabled' => false,
        'isRemoteEnabled' => true,
        'isHtml5ParserEnabled' => true,
        'defaultFont' => 'dejavusans',
        'enable_font_subsetting' => true,
        'debugCss' => false,
        'debugLayout' => false,
    ],

    'font_dir' => storage_path('fonts/'),

    'font_cache' => storage_path('fonts/'),

    'font_data' => [
        'tajawal' => [
            'R' => storage_path('fonts/Tajawal-Regular.ttf'),
            'useOTL' => 0xFF,
            'useKashida' => 75,
        ],
    ],

    'chroot' => base_path(),

    'log_output_file' => storage_path('logs/dompdf.html'),
];

