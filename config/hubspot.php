<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HubSpot API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para la integración con HubSpot API
    |
    */

    'token' => env('HUBSPOT_TOKEN'),
    
    'api_base_url' => 'https://api.hubapi.com',
    
    'rate_limit' => [
        'requests_per_second' => 10,
        'burst_limit' => 100,
    ],
    
    'endpoints' => [
        'contacts' => '/crm/v3/objects/contacts',
        'deals' => '/crm/v3/objects/deals',
        'properties' => '/crm/v3/properties',
    ],
    
    'sync' => [
        'batch_size' => 100,
        'timeout' => 30,
        'retry_attempts' => 3,
        'retry_delay' => 2, // seconds
    ],
    
    'mapping' => [
        // Mapeo de campos de HubSpot a campos de Laravel
        'contact_fields' => [
            'hs_object_id' => 'hubspot_id',
            'firstname' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'createdate' => 'fecha_registro',  // Fecha de creación en HubSpot
            'lastmodifieddate' => 'updated_at',
        ],
        
        // Propiedades personalizadas que se buscarán
        'custom_properties' => [
            'xante_id',
            'xante_client_id',
            'id_xante',
            'client_xante_id',
        ],
    ],
];
