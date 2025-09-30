<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Datos Generales - Fase I</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 10pt; 
            line-height: 1.3;
            margin: 15px;
        }
        .header { 
            text-align: center; 
            margin-bottom: 20px; 
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
        }
        .logo {
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 8px;
        }
        .title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .section { 
            margin-bottom: 15px; 
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 8px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 35%;
            padding: 4px 8px 4px 0;
            vertical-align: top;
            border-bottom: 1px dotted #ccc;
        }
        .info-value {
            display: table-cell;
            padding: 4px 0;
            vertical-align: top;
            border-bottom: 1px dotted #ccc;
        }
        .two-column {
            display: table;
            width: 100%;
        }
        .column {
            display: table-cell;
            width: 48%;
            padding-right: 2%;
            vertical-align: top;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8pt;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">XANTE.MX</div>
        <div class="title">DATOS GENERALES - FASE I</div>
        <p>ID Xante: {{ $xante_id }} | Fecha: {{ $fecha_actual }}</p>
    </div>

    <div class="section">
        <div class="section-title">DATOS PERSONALES DEL TITULAR</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nombre Completo:</div>
                <div class="info-value">{{ $holder_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha de Nacimiento:</div>
                <div class="info-value">{{ $holder_birthdate }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">CURP:</div>
                <div class="info-value">{{ $holder_curp }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">RFC:</div>
                <div class="info-value">{{ $holder_rfc }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Estado Civil:</div>
                <div class="info-value">{{ ucfirst(str_replace('_', ' ', $holder_civil_status)) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Ocupación:</div>
                <div class="info-value">{{ $holder_occupation }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Correo Electrónico:</div>
                <div class="info-value">{{ $holder_email }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Teléfono:</div>
                <div class="info-value">{{ $holder_phone }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Domicilio Actual:</div>
                <div class="info-value">{{ $holder_current_address }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Municipio:</div>
                <div class="info-value">{{ $holder_municipality }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Estado:</div>
                <div class="info-value">{{ $holder_state }}</div>
            </div>
        </div>
    </div>

    @if($spouse_name)
    <div class="section">
        <div class="section-title">DATOS PERSONALES DEL CÓNYUGE</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nombre Completo:</div>
                <div class="info-value">{{ $spouse_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">CURP:</div>
                <div class="info-value">{{ $spouse_curp }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">RFC:</div>
                <div class="info-value">{{ $spouse_rfc }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Correo Electrónico:</div>
                <div class="info-value">{{ $spouse_email }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Teléfono:</div>
                <div class="info-value">{{ $spouse_phone }}</div>
            </div>
        </div>
    </div>
    @endif

    <div class="section">
        <div class="section-title">INFORMACIÓN DE LA PROPIEDAD</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Domicilio del Convenio:</div>
                <div class="info-value">{{ $domicilio_convenio }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Comunidad:</div>
                <div class="info-value">{{ $comunidad }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Tipo de Vivienda:</div>
                <div class="info-value">{{ $tipo_vivienda }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Prototipo:</div>
                <div class="info-value">{{ $prototipo }}</div>
            </div>
            @if($lote)
            <div class="info-row">
                <div class="info-label">Lote:</div>
                <div class="info-value">{{ $lote }}</div>
            </div>
            @endif
            @if($manzana)
            <div class="info-row">
                <div class="info-label">Manzana:</div>
                <div class="info-value">{{ $manzana }}</div>
            </div>
            @endif
            @if($etapa)
            <div class="info-row">
                <div class="info-label">Etapa:</div>
                <div class="info-value">{{ $etapa }}</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Municipio:</div>
                <div class="info-value">{{ $municipio_propiedad }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Estado:</div>
                <div class="info-value">{{ $estado_propiedad }}</div>
            </div>
        </div>
    </div>

    @if($ac_name || $private_president_name)
    <div class="section">
        <div class="section-title">CONTACTOS ADICIONALES</div>
        <div class="two-column">
            @if($ac_name)
            <div class="column">
                <strong>Contacto AC:</strong><br>
                Nombre: {{ $ac_name }}<br>
                Teléfono: {{ $ac_phone }}<br>
                @if($ac_quota)
                Cuota: ${{ number_format($ac_quota, 2) }}<br>
                @endif
            </div>
            @endif
            @if($private_president_name)
            <div class="column">
                <strong>Presidente de Privada:</strong><br>
                Nombre: {{ $private_president_name }}<br>
                Teléfono: {{ $private_president_phone }}<br>
                @if($private_president_quota)
                Cuota: ${{ number_format($private_president_quota, 2) }}<br>
                @endif
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="section">
        <div class="section-title">INFORMACIÓN FINANCIERA</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Valor del Convenio:</div>
                <div class="info-value">${{ number_format($valor_convenio, 2) }} MXN</div>
            </div>
            <div class="info-row">
                <div class="info-label">Monto de Crédito:</div>
                <div class="info-value">${{ number_format($monto_credito, 2) }} MXN</div>
            </div>
            <div class="info-row">
                <div class="info-label">Tipo de Crédito:</div>
                <div class="info-value">{{ ucfirst($tipo_credito) }}</div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Este documento fue generado automáticamente por el sistema XANTE.MX el {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>Documento confidencial - Solo para uso interno de XANTE.MX</p>
    </div>
</body>
</html>
