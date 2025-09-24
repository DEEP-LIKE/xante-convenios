<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convenio de Compraventa - {{ $agreement->client->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .subtitle {
            font-size: 14px;
            color: #666;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 15px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 30%;
            padding: 5px 10px 5px 0;
            vertical-align: top;
        }
        .info-value {
            display: table-cell;
            padding: 5px 0;
            vertical-align: top;
        }
        .financial-summary {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .financial-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .financial-label {
            font-weight: bold;
        }
        .financial-value {
            font-weight: bold;
            color: #007bff;
        }
        .total-row {
            border-top: 2px solid #007bff;
            padding-top: 8px;
            margin-top: 15px;
        }
        .total-row .financial-value {
            font-size: 16px;
            color: #28a745;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .signature-section {
            margin-top: 50px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 20px;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 5px;
            font-size: 11px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">XANTE.MX</div>
        <div class="title">CONVENIO DE COMPRAVENTA DE PROPIEDAD</div>
        <div class="subtitle">Convenio No. {{ str_pad($agreement->id, 6, '0', STR_PAD_LEFT) }}</div>
        <div class="subtitle">Fecha: {{ $agreement->created_at->format('d/m/Y') }}</div>
    </div>

    <div class="section">
        <div class="section-title">INFORMACIÓN DEL CLIENTE</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nombre Completo:</div>
                <div class="info-value">{{ $agreement->client->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha de Nacimiento:</div>
                <div class="info-value">{{ $agreement->client->birthdate->format('d/m/Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">CURP:</div>
                <div class="info-value">{{ $agreement->client->curp }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">RFC:</div>
                <div class="info-value">{{ $agreement->client->rfc }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Correo Electrónico:</div>
                <div class="info-value">{{ $agreement->client->email }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Teléfono:</div>
                <div class="info-value">{{ $agreement->client->phone }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Dirección Actual:</div>
                <div class="info-value">{{ $agreement->client->current_address }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Municipio:</div>
                <div class="info-value">{{ $agreement->client->municipality }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Estado:</div>
                <div class="info-value">{{ $agreement->client->state }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">INFORMACIÓN DE LA PROPIEDAD</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Dirección:</div>
                <div class="info-value">{{ $agreement->property->address }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Comunidad:</div>
                <div class="info-value">{{ $agreement->property->community }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Tipo de Propiedad:</div>
                <div class="info-value">
                    @switch($agreement->property->property_type)
                        @case('casa') Casa @break
                        @case('departamento') Departamento @break
                        @case('condominio') Condominio @break
                        @case('terreno') Terreno @break
                        @case('local_comercial') Local Comercial @break
                        @case('oficina') Oficina @break
                        @case('bodega') Bodega @break
                        @default {{ $agreement->property->property_type }}
                    @endswitch
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Valor de la Propiedad:</div>
                <div class="info-value">${{ number_format($agreement->property->value, 2) }} MXN</div>
            </div>
            @if($agreement->property->mortgage_amount)
            <div class="info-row">
                <div class="info-label">Monto de Hipoteca:</div>
                <div class="info-value">${{ number_format($agreement->property->mortgage_amount, 2) }} MXN</div>
            </div>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title">DETALLES FINANCIEROS DEL CONVENIO</div>
        <div class="financial-summary">
            <div class="financial-row">
                <span class="financial-label">Valor sin Fideicomiso:</span>
                <span class="financial-value">${{ number_format($agreement->calculation->value_without_escrow, 2) }} MXN</span>
            </div>
            <div class="financial-row">
                <span class="financial-label">Gastos Notariales:</span>
                <span class="financial-value">${{ number_format($agreement->calculation->notarial_expenses, 2) }} MXN</span>
            </div>
            <div class="financial-row">
                <span class="financial-label">Valor de Compra:</span>
                <span class="financial-value">${{ number_format($agreement->calculation->purchase_value, 2) }} MXN</span>
            </div>
            <div class="financial-row">
                <span class="financial-label">Diferencia Valor Vivienda:</span>
                <span class="financial-value">${{ number_format($agreement->calculation->difference_value, 2) }} MXN</span>
            </div>
            <div class="financial-row">
                <span class="financial-label">Exento de ISR:</span>
                <span class="financial-value">{{ $agreement->calculation->is_isr_exempt ? 'Sí' : 'No' }}</span>
            </div>
            <div class="financial-row total-row">
                <span class="financial-label">PAGO TOTAL:</span>
                <span class="financial-value">${{ number_format($agreement->calculation->total_payment, 2) }} MXN</span>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">TÉRMINOS Y CONDICIONES</div>
        <p>El presente convenio de compraventa se celebra entre las partes mencionadas, estableciendo los siguientes términos:</p>
        <ul>
            <li>El comprador se compromete a realizar el pago total acordado de ${{ number_format($agreement->calculation->total_payment, 2) }} MXN.</li>
            <li>Los gastos notariales por un monto de ${{ number_format($agreement->calculation->notarial_expenses, 2) }} MXN corren por cuenta del comprador.</li>
            <li>La propiedad se entrega libre de gravámenes y en las condiciones actuales.</li>
            <li>Cualquier modificación a este convenio deberá ser acordada por escrito entre las partes.</li>
            <li>Este convenio se rige por las leyes mexicanas aplicables.</li>
        </ul>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line">
                <strong>{{ $agreement->client->name }}</strong><br>
                Comprador
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                <strong>XANTE.MX</strong><br>
                Representante Legal
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Este documento fue generado automáticamente por el sistema XANTE.MX el {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>Para cualquier consulta, contacte a: info@xante.mx | Tel: +52 (55) 1234-5678</p>
    </div>
</body>
</html>
