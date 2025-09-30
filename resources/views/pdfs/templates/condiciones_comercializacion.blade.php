<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Condiciones para Comercialización</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 11pt; 
            line-height: 1.5;
            margin: 20px;
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
            margin-bottom: 10px;
        }
        .section { 
            margin-bottom: 25px; 
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 15px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .property-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #007bff;
        }
        .conditions-list {
            counter-reset: condition-counter;
            list-style: none;
            padding: 0;
        }
        .conditions-list li {
            counter-increment: condition-counter;
            margin-bottom: 15px;
            padding: 12px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border-left: 3px solid #28a745;
            position: relative;
        }
        .conditions-list li::before {
            content: counter(condition-counter);
            position: absolute;
            left: -15px;
            top: 50%;
            transform: translateY(-50%);
            background-color: #28a745;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 12px;
        }
        .financial-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: white;
        }
        .financial-table th {
            background-color: #007bff;
            color: white;
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .financial-table td {
            padding: 10px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        .financial-table .highlight {
            background-color: #fff3cd;
            font-weight: bold;
        }
        .important-note {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
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
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">XANTE.MX</div>
        <div class="title">CONDICIONES PARA COMERCIALIZACIÓN</div>
        <p>Fecha de emisión: {{ $fecha_actual }}</p>
    </div>

    <div class="property-info">
        <h3 style="margin-top: 0; color: #007bff;">INFORMACIÓN DE LA PROPIEDAD</h3>
        <p><strong>Propietario:</strong> {{ $holder_name }}</p>
        <p><strong>Domicilio:</strong> {{ $domicilio_convenio }}</p>
        <p><strong>Comunidad:</strong> {{ $comunidad }}</p>
        <p><strong>Tipo de Vivienda:</strong> {{ $tipo_vivienda }} - {{ $prototipo }}</p>
        @if($lote || $manzana || $etapa)
        <p><strong>Ubicación:</strong> 
            @if($lote) Lote {{ $lote }} @endif
            @if($manzana) Manzana {{ $manzana }} @endif
            @if($etapa) Etapa {{ $etapa }} @endif
        </p>
        @endif
    </div>

    <div class="section">
        <div class="section-title">CONDICIONES COMERCIALES</div>
        <table class="financial-table">
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th>Monto</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Precio de Promoción</strong></td>
                    <td>${{ number_format($precio_promocion, 2) }} MXN</td>
                    <td>Precio público de venta</td>
                </tr>
                <tr>
                    <td><strong>Valor del Convenio</strong></td>
                    <td>${{ number_format($valor_convenio, 2) }} MXN</td>
                    <td>Valor base para cálculos</td>
                </tr>
                <tr>
                    <td><strong>Comisión XANTE</strong></td>
                    <td>${{ number_format($comision_total_pagar, 2) }} MXN</td>
                    <td>{{ number_format(($comision_total_pagar / $valor_convenio) * 100, 2) }}% del valor del convenio</td>
                </tr>
                <tr class="highlight">
                    <td><strong>Ganancia Final del Propietario</strong></td>
                    <td>${{ number_format($ganancia_final, 2) }} MXN</td>
                    <td>Monto neto a recibir</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">CONDICIONES GENERALES DE COMERCIALIZACIÓN</div>
        <ol class="conditions-list">
            <li>
                <strong>Autorización de Promoción:</strong> El propietario autoriza expresamente a XANTE para promocionar, publicitar y comercializar la propiedad a través de todos los medios disponibles, incluyendo plataformas digitales, redes sociales y medios tradicionales.
            </li>
            <li>
                <strong>Exclusividad:</strong> Este acuerdo otorga a XANTE la exclusividad para la comercialización de la propiedad durante la vigencia del convenio, prohibiendo al propietario contratar otros servicios de comercialización inmobiliaria.
            </li>
            <li>
                <strong>Precio de Venta:</strong> El precio de promoción establecido es de ${{ number_format($precio_promocion, 2) }} MXN y podrá ser ajustado únicamente mediante acuerdo mutuo por escrito entre las partes.
            </li>
            <li>
                <strong>Comisión por Servicios:</strong> La comisión de XANTE es del {{ number_format(($comision_total_pagar / $valor_convenio) * 100, 2) }}% sobre el valor del convenio, equivalente a ${{ number_format($comision_total_pagar, 2) }} MXN, que será descontada del precio de venta al momento del cierre.
            </li>
            <li>
                <strong>Documentación Requerida:</strong> El propietario se compromete a proporcionar toda la documentación legal necesaria para la venta, incluyendo escrituras, identificaciones oficiales, comprobantes de servicios y cualquier otro documento que XANTE considere necesario.
            </li>
            <li>
                <strong>Estado de la Propiedad:</strong> La propiedad se comercializa en su estado actual. Cualquier mejora o reparación necesaria será acordada previamente entre las partes y su costo será responsabilidad del propietario.
            </li>
            <li>
                <strong>Visitas y Mostrado:</strong> El propietario autoriza a XANTE y sus representantes a mostrar la propiedad a posibles compradores en horarios razonables, previa coordinación telefónica.
            </li>
            <li>
                <strong>Proceso de Venta:</strong> XANTE se encargará de todo el proceso de comercialización, incluyendo la búsqueda de compradores, negociación de términos, y coordinación del proceso de cierre con notario público.
            </li>
            <li>
                <strong>Gastos Adicionales:</strong> Los gastos notariales, impuestos y derechos correspondientes a la operación de compraventa serán cubiertos según lo establecido por la ley y los acuerdos particulares con el comprador.
            </li>
            <li>
                <strong>Vigencia del Acuerdo:</strong> Este acuerdo permanecerá vigente hasta la venta exitosa de la propiedad o hasta que cualquiera de las partes decida terminarlo mediante notificación por escrito con 30 días de anticipación.
            </li>
        </ol>
    </div>

    @if($monto_credito > 0)
    <div class="section">
        <div class="section-title">INFORMACIÓN DE CRÉDITO</div>
        <p><strong>Monto de Crédito:</strong> ${{ number_format($monto_credito, 2) }} MXN</p>
        <p><strong>Tipo de Crédito:</strong> {{ ucfirst($tipo_credito) }}</p>
        @if($cancelacion_hipoteca > 0)
        <p><strong>Cancelación de Hipoteca:</strong> ${{ number_format($cancelacion_hipoteca, 2) }} MXN</p>
        @endif
    </div>
    @endif

    <div class="important-note">
        <h4 style="margin-top: 0;">⚠️ IMPORTANTE</h4>
        <p>Este documento establece las condiciones bajo las cuales XANTE comercializará la propiedad. El propietario declara que la información proporcionada es veraz y completa, y que tiene las facultades legales para celebrar este acuerdo.</p>
        <p><strong>La ganancia final de ${{ number_format($ganancia_final, 2) }} MXN está sujeta a la deducción de impuestos aplicables según la legislación vigente.</strong></p>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line">
                <strong>{{ $holder_name }}</strong><br>
                Propietario
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                <strong>XANTE.MX</strong><br>
                Representante Comercial
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Este documento fue generado automáticamente por el sistema XANTE.MX el {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>Para consultas: info@xante.mx | Tel: +52 (55) 1234-5678</p>
        <p><strong>Documento confidencial</strong> - Prohibida su reproducción sin autorización</p>
    </div>
</body>
</html>
