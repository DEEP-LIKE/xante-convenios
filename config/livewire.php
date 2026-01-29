<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Temporary File Uploads
    |--------------------------------------------------------------------------
    |
    | Livewire handles file uploads by storing them in a temporary directory
    | until the actual validation and storage happens. If you are using
    | S3 as your default disk, Livewire tries to upload directly to S3.
    |
    | We force 'local' disk here to avoid CORS issues with S3 during the
    | temporary upload phase.
    |
    */

    'temporary_file_upload' => [
        'disk' => 'local',        // Forzamos el uso de disco local para el proceso temporal
        'directory' => 'livewire-tmp',
        'middleware' => 'throttle:60,1',
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'pdf',
        ],
        'max_upload_time' => 5, // minutes
    ],

];
