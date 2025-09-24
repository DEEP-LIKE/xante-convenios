<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Porcentajes Xante
    |--------------------------------------------------------------------------
    |
    | Estos valores son utilizados en la calculadora financiera para
    | calcular comisiones, IVA y otros gastos automáticamente.
    |
    */

    // Porcentajes de comisión
    'comision_sin_iva_default' => 6.50, // % Comisión (Sin IVA) por defecto
    'comision_iva_incluido' => 7.54, // % Comisión IVA incluido (ESTE VALOR YA NO SE USA - SE CALCULA DINÁMICAMENTE)
    
    // Gastos fijos
    'isr_default' => 0, // ISR por defecto 
    'cancelacion_hipoteca_default' => 20000, // Cancelación de hipoteca por defecto (10000*2)
    'total_gastos_fi_default' => 20000, // Total Gastos FI (Venta) por defecto
    
    // Otros valores por defecto
    'precio_promocion_incremento' => 9.0, // % de incremento para precio promoción (1.09 = 9%) - CORREGIDO
    'monto_credito_default' => 800000, // Monto de crédito por defecto
    'tipo_credito_default' => 'BANCARIO',
    'otro_banco_default' => 'NO APLICA',
    
    // Multiplicadores para cálculos
    'iva_multiplier' => 1.16, // Multiplier para agregar IVA (16%)
    'precio_promocion_multiplier' => 1.09, // Multiplier para precio promoción
];