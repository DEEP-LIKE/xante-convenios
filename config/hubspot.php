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
            // Propiedades básicas del deal
            'dealname',
            'amount',
            'dealstage',
            'closedate',
            'createdate',
            'estatus_de_convenio',
            'num_associated_contacts',
            'hs_object_id',
            'hs_lastmodifieddate',

            // Datos del titular
            'nombre_completo',
            'email',
            'phone',
            'mobilephone',
            'telefono_oficina',
            'curp',
            'rfc',
            'estado_civil',
            'ocupacion',

            // Domicilio del titular
            'domicilio_actual',
            'numero_casa',
            'colonia',
            'codigo_postal',
            'municipio',
            'estado',

            // Datos del cónyuge
            'nombre_completo_conyuge',
            'email_conyuge',
            'telefono_movil_conyuge',
            'curp_conyuge',

            // Domicilio del cónyuge
            'domicilio_actual_conyuge',
            'numero_casa_conyuge',
            'colonia_conyuge',
            'codigo_postal_conyuge',
            'municipio_conyuge',
            'estado_conyuge',

            // Datos de la propiedad (nombres correctos de HubSpot API)
            'nombre_del_desarrollo',    // Nombre del desarrollo (ej: Real Granada)
            'calle_o_privada_',         // Calle / Privada y Número #
            'tipo_de_inmueble_',        // Tipo de inmueble (Casa, Departamento, etc.)
            'ciudad',                   // Ciudad
            'state',                    // Estado/región
            'hipotecada',               // Hipotecada (Sí/No)
            'tipo_de_hipoteca',         // Tipo de hipoteca (Infonavit, Bancaria, etc.)
            'niveles_casa',             // Niveles de la casa

            // Datos financieros
            'valor_convenio',
            'precio_promocion',
            'comision_total_pagar',
            'ganancia_final',
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
                            'value' => 'Aceptado',
                        ],
                    ],
                ],
            ],
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

            // Nuevos campos expandidos
            'address' => 'current_address',
            'city' => 'municipality',
            'state' => 'state',
            'zip' => 'postal_code',
            'colonia' => 'neighborhood',
            'date_of_birth' => 'birthdate',
            'jobtitle' => 'occupation',
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
