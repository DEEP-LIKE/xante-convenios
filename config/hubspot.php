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
        'deals_search' => '/crm/v3/objects/deals/search',
        'properties' => '/crm/v3/properties',
    ],
    
    'sync' => [
        'batch_size' => 100,
        'timeout' => 30,
        'retry_attempts' => 3,
        'retry_delay' => 2, // seconds
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Deal Synchronization Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para sincronización basada en Deals
    |
    */
    'deal_sync' => [
        'status_field' => 'estatus_de_convenio',
        'accepted_value' => 'Aceptado',
        'properties' => [
            'dealname',
            'amount',
            'dealstage',
            'closedate',
            'createdate',
            'estatus_de_convenio',
            'num_associated_contacts',
            'nombre_del_titular',
            'hs_object_id',
            'hs_lastmodifieddate',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Search Filters
    |--------------------------------------------------------------------------
    |
    | Filtros predefinidos para búsquedas en HubSpot
    |
    */
    'filters' => [
        'deal_accepted' => [
            'filterGroups' => [
                [
                    'filters' => [
                        [
                            'propertyName' => 'estatus_de_convenio',
                            'operator' => 'EQ',
                            'value' => 'Aceptado'
                        ]
                    ]
                ]
            ]
        ],
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
