<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Checklist de Expediente Básico</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 11pt; 
            line-height: 1.4;
            margin: 20px;
        }
        .header { 
            text-align: center; 
            margin-bottom: 25px; 
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
        }
        .logo {
            font-size: 22px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 8px;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .client-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .section { 
            margin-bottom: 20px; 
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 12px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .checklist {
            list-style: none;
            padding: 0;
        }
        .checklist li {
            margin-bottom: 8px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            position: relative;
            padding-left: 35px;
        }
        .checkbox {
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 15px;
            height: 15px;
            border: 2px solid #333;
            display: inline-block;
        }
        .required {
            background-color: #fff3cd;
            border-color: #ffeaa7;
        }
        .optional {
            background-color: #e2e3e5;
            border-color: #ced4da;
        }
        .notes-section {
            margin-top: 30px;
            border: 1px solid #ddd;
            padding: 15px;
            min-height: 100px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .signature-area {
            margin-top: 40px;
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
            margin-top: 50px;
            padding-top: 5px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">XANTE.MX</div>
        <div class="title">CHECKLIST DE EXPEDIENTE BÁSICO</div>
        <p>Fecha: {{ $fecha_actual }}</p>
    </div>

    <div class="client-info">
        <strong>Cliente:</strong> {{ $holder_name }}<br>
        <strong>ID Xante:</strong> {{ $xante_id }}<br>
        <strong>Propiedad:</strong> {{ $domicilio_convenio }}<br>
        <strong>Comunidad:</strong> {{ $comunidad }}
    </div>

    <div class="section">
        <div class="section-title">DOCUMENTACIÓN DEL TITULAR</div>
        <ul class="checklist">
            <li class="required">
                <span class="checkbox"></span>
                <strong>INE</strong> (A color, tamaño original, no fotos)
            </li>
            <li class="required">
                <span class="checkbox"></span>
                <strong>CURP</strong> (Mes corriente)
            </li>
            <li class="required">
                <span class="checkbox"></span>
                <strong>Constancia de Situación Fiscal</strong>
            </li>
            <li class="required">
                <span class="checkbox"></span>
                <strong>Comprobante de Domicilio Vivienda</strong>
            </li>
            <li class="required">
                <span class="checkbox"></span>
                <strong>Comprobante de Domicilio Titular</strong>
            </li>
            <li class="required">
                <span class="checkbox"></span>
                <strong>Acta de Nacimiento</strong>
            </li>
            <li class="optional">
                <span class="checkbox"></span>
                <strong>Acta de Matrimonio</strong> (Si aplica)
            </li>
            <li class="required">
                <span class="checkbox"></span>
                <strong>Carátula Estado de Cuenta Bancario</strong>
            </li>
        </ul>
    </div>

    @if($spouse_name)
    <div class="section">
        <div class="section-title">DOCUMENTACIÓN DEL CÓNYUGE</div>
        <p><strong>Cónyuge:</strong> {{ $spouse_name }}</p>
        <ul class="checklist">
            <li class="required">
                <span class="checkbox"></span>
                <strong>INE del Cónyuge</strong> (A color, tamaño original)
            </li>
            <li class="required">
                <span class="checkbox"></span>
                <strong>CURP del Cónyuge</strong> (Mes corriente)
            </li>
            <li class="required">
                <span class="checkbox"></span>
                <strong>Constancia de Situación Fiscal del Cónyuge</strong>
            </li>
            <li class="required">
                <span class="checkbox"></span>
                <strong>Acta de Nacimiento del Cónyuge</strong>
            </li>
        </ul>
    </div>
    @endif

    <div class="section">
        <div class="section-title">DOCUMENTACIÓN DE LA PROPIEDAD</div>
        <ul class="checklist">
            <li class="required">
                <span class="checkbox"></span>
                <strong>Instrumento Notarial</strong>
            </li>
            <li class="required">
                <span class="checkbox"></span>
                <strong>Recibo Predial</strong> (Mes corriente)
            </li>
            <li class="required">
                <span class="checkbox"></span>
                <strong>Recibo de Agua</strong> (Mes corriente)
            </li>
            <li class="required">
                <span class="checkbox"></span>
                <strong>Recibo CFE</strong> con datos fiscales
            </li>
        </ul>
    </div>

    <div class="section">
        <div class="section-title">INFORMACIÓN FINANCIERA</div>
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <tr style="background-color: #f8f9fa;">
                <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold;">Concepto</td>
                <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold;">Monto</td>
            </tr>
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px;">Valor del Convenio</td>
                <td style="border: 1px solid #ddd; padding: 8px;">${{ number_format($valor_convenio, 2) }} MXN</td>
            </tr>
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px;">Precio de Promoción</td>
                <td style="border: 1px solid #ddd; padding: 8px;">${{ number_format($precio_promocion, 2) }} MXN</td>
            </tr>
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px;">Comisión Total a Pagar</td>
                <td style="border: 1px solid #ddd; padding: 8px;">${{ number_format($comision_total_pagar, 2) }} MXN</td>
            </tr>
            <tr style="background-color: #d4edda;">
                <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold;">Ganancia Final</td>
                <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold;">${{ number_format($ganancia_final, 2) }} MXN</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">OBSERVACIONES Y NOTAS</div>
        <div class="notes-section">
            <p><strong>Instrucciones:</strong></p>
            <ul>
                <li>Todos los documentos marcados como "requeridos" son obligatorios</li>
                <li>Los documentos deben estar vigentes y en buen estado</li>
                <li>Las copias deben ser legibles y a color cuando se especifique</li>
                <li>Verificar que los nombres coincidan en todos los documentos</li>
            </ul>
            <br>
            <p><strong>Notas adicionales:</strong></p>
            <div style="border-bottom: 1px dotted #ccc; margin-bottom: 8px; height: 20px;"></div>
            <div style="border-bottom: 1px dotted #ccc; margin-bottom: 8px; height: 20px;"></div>
            <div style="border-bottom: 1px dotted #ccc; margin-bottom: 8px; height: 20px;"></div>
        </div>
    </div>

    <div class="signature-area">
        <div class="signature-box">
            <div class="signature-line">
                <strong>{{ $holder_name }}</strong><br>
                Cliente
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                <strong>ASESOR XANTE</strong><br>
                Responsable del Expediente
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Este documento fue generado automáticamente por el sistema XANTE.MX el {{ now()->format('d/m/Y H:i:s') }}</p>
        <p><strong>Importante:</strong> Este checklist debe ser completado antes de proceder con el convenio</p>
    </div>
</body>
</html>
