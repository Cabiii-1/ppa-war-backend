<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Snappy PDF / Image Configuration
    |--------------------------------------------------------------------------
    |
    | This option contains settings for PDF generation.
    |
    | Enabled:
    |
    |    Whether to load PDF / Image generation.
    |
    | Binary:
    |
    |    The file path of the wkhtml binary.
    |
    | Timout:
    |
    |    The amount of time to wait (in seconds) before PDF / Image generation is stopped.
    |    Setting this to false disables the timeout (unlimited processing time).
    |
    | Options:
    |
    |    The wkhtmltopdf command options. These are passed directly to wkhtmltopdf.
    |    See https://wkhtmltopdf.org/usage/wkhtmltopdf.txt for all options.
    |
    | Env:
    |
    |    The environment variables to pass to the wkhtmltopdf process.
    |
    */

    'pdf' => [
        'enabled' => true,
        'binary' => env('WKHTML_PDF_BINARY', '"C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe"'),
        'timeout' => false,
        'options' => [
            'enable-local-file-access' => true,
            'page-size' => 'A4',
            'orientation' => 'Portrait',
            'margin-top' => '10mm',
            'margin-right' => '10mm',
            'margin-bottom' => '10mm',
            'margin-left' => '10mm',
            'encoding' => 'UTF-8',
            'print-media-type' => true,
            'no-outline' => true,
            'disable-smart-shrinking' => true,
        ],
        'env' => [],
    ],

    'image' => [
        'enabled' => true,
        'binary' => env('WKHTML_IMG_BINARY', '"C:\Program Files\wkhtmltopdf\bin\wkhtmltoimage.exe"'),
        'timeout' => false,
        'options' => [],
        'env' => [],
    ],

];
