{{-- resources/views/pdfs/templates/checklist_completo.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist Completo de Expediente Inmobiliario</title>
    <style>
        @page {
            size: letter;
            margin: 2.5cm 3.5cm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, Helvetica, sans-serif;
            padding: 40px;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
        }
        
        .page-container {
            width: 100%;
            max-width: 21.59cm;
            margin: 0 auto;
        }
        
        /* HEADER */
        .header {
            text-align: right;
            margin-bottom: 5px;
            font-size: 10pt;
        }
        
        .header .fecha {
            font-weight: bold;
        }
        
        /* LOGO */
        .logo-section {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .logo {
            width: 120px;
            height: auto;
        }

        .header-divider {
            border: none;
            border-top: 3px solid #7DC142;
            margin: 6px 0 20px 0;
        }
        
        /* TÍTULO */
        .main-title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 12px;
            color: #5B2D8E;
            line-height: 1.3;
        }
        
        /* SALUDO */
        .greeting {
            text-align: justify;
            margin-bottom: 10px;
            line-height: 1.5;
        }
        
        .greeting-name {
            font-weight: bold;
        }
        
        /* PÁRRAFOS */
        .paragraph {
            text-align: justify;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        /* CLIENTE INFO BOX */
        .client-box {
            margin: 10px 0 15px 0;
            padding: 8px 14px;
            border: 1.5px solid #5B2D8E;
            background-color: #f8f4fb;
            border-radius: 6px;
        }
        
        .client-table {
            width: 100%;
            border-collapse: collapse;
        }

        .client-table td {
            padding: 2px 0;
            vertical-align: middle;
            font-size: 9.5pt;
        }

        .client-table .label {
            font-weight: bold;
            color: #5B2D8E;
            white-space: nowrap;
            width: 85px;
        }
        
        .client-table .value {
            font-weight: bold;
            color: #000;
        }
        
        /* SECCIONES DE DOCUMENTACIÓN */
        .doc-section {
            margin: 20px 0;
        }
        
        .doc-section-title {
            background-color: #7DC142;
            color: #fff;
            font-weight: bold;
            font-size: 10.5pt;
            padding: 6px 12px;
            margin-bottom: 10px;
            text-transform: uppercase;
            border-radius: 4px;
        }
        
        /* TABLA DE DOCUMENTOS */
        .doc-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .doc-table td {
            border: 1px solid #ddd;
            padding: 6px 10px;
            vertical-align: middle;
            font-size: 9.5pt;
        }
        
        .doc-number {
            width: 32px;
            text-align: center;
            font-weight: bold;
            background-color: #f5f5f5;
            color: #5B2D8E;
        }
        
        .doc-name {
            font-weight: normal;
        }
        
        .doc-checkbox {
            width: 35px;
            text-align: center;
        }
        
        .checkbox-square {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #5B2D8E;
            border-radius: 3px;
            vertical-align: middle;
        }

        .checkbox-checked {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #7DC142;
            background-color: #7DC142;
            border-radius: 3px;
            vertical-align: middle;
            position: relative;
        }

        .checkbox-checked::after {
            content: "✓";
            color: #fff;
            font-size: 14px;
            font-weight: bold;
            position: absolute;
            top: -2px;
            left: 2px;
        }
        
        /* NOTAS */
        .notes-section {
            margin: 20px 0;
            background: #fdfaf3;
            border-left: 4px solid #f59e0b;
            padding: 12px 16px;
            border-radius: 4px;
        }
        
        .note-item {
            margin-bottom: 8px;
            text-align: justify;
            line-height: 1.5;
            font-size: 9pt;
        }
        
        .note-emphasis {
            font-style: italic;
            margin-top: 15px;
            text-align: center;
            font-weight: bold;
            color: #5B2D8E;
        }

        .page-footer {
            margin-top: 30px;
            border-top: 1px solid #ccc;
            padding-top: 8px;
            font-size: 8pt;
            color: #666;
            text-align: center;
        }

        .footer-bar {
            background-color: #7DC142;
            height: 6px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="page-container">
        
        {{-- Función helper para verificar si documento está cargado --}}
        @php
            $uploadedDocs = $uploadedDocuments ?? [];
            $updated = $isUpdated ?? false;
            $isChecked = function($documentType) use ($uploadedDocs, $updated) {
                if (!$updated) return false;
                return in_array($documentType, $uploadedDocs);
            };
        @endphp
        
        {{-- HEADER --}}
        <div class="header">
            <span class="fecha">{{ $day ?? now()->format('d') }} de {{ $month ?? 'enero' }} de {{ $year ?? now()->format('Y') }}</span>
        </div>

        {{-- LOGO --}}
        <div class="logo-section">
            <img src="{{ $logo_path }}" alt="Xante Logo" class="logo">
        </div>
        <hr class="header-divider">

        {{-- TÍTULO PRINCIPAL --}}
        <div class="main-title">
            CHECKLIST COMPLETO DE EXPEDIENTE INMOBILIARIO
        </div>

        {{-- SALUDO --}}
        <div class="greeting">
            Estimado/a <span class="greeting-name">{{ strtoupper($holder_name ?? 'CLIENTE') }}</span>: Este documento detalla la totalidad de la documentación requerida para integrar el expediente completo de su propiedad ante Notaría y entidades financieras al momento de confirmar la venta.
        </div>

        {{-- INFORMACIÓN DEL CLIENTE --}}
        <div class="client-box">
            <table class="client-table">
                <tr>
                    <td class="label">CLIENTE:</td>
                    <td class="value" colspan="3">{{ strtoupper($holder_name ?? '____________________') }}</td>
                </tr>
                <tr>
                    <td class="label">INMUEBLE:</td>
                    <td class="value" colspan="3">{{ strtoupper($domicilio_convenio ?? '') }} {{ strtoupper($property_full_community ?? '') }}</td>
                </tr>
                <tr>
                    <td class="label">UBICACIÓN:</td>
                    <td class="value">{{ strtoupper($property_municipality ?? '') }}, {{ strtoupper($property_state ?? '') }}</td>
                    <td class="label" style="width: 50px; text-align: right; padding-right: 8px;">TIPO:</td>
                    <td class="value" style="width: 70px;">{{ strtoupper($tipo_vivienda ?? '') }}</td>
                </tr>
            </table>
        </div>

        {{-- DOCUMENTACIÓN TITULAR --}}
        <div class="doc-section">
            <div class="doc-section-title">1. DOCUMENTACIÓN DEL TITULAR</div>
            
            <table class="doc-table">
                <tr>
                    <td class="doc-number">1</td>
                    <td class="doc-name">Identificación Oficial Vigente (INE / Pasaporte a color)</td>
                    <td class="doc-checkbox">
                        <span class="{{ $isChecked('titular_ine') ? 'checkbox-checked' : 'checkbox-square' }}"></span>
                    </td>
                </tr>
                <tr>
                    <td class="doc-number">2</td>
                    <td class="doc-name">CURP (Certificada, emisión reciente)</td>
                    <td class="doc-checkbox">
                        <span class="{{ $isChecked('titular_curp') ? 'checkbox-checked' : 'checkbox-square' }}"></span>
                    </td>
                </tr>
                <tr>
                    <td class="doc-number">3</td>
                    <td class="doc-name">Constancia de Situación Fiscal (Mes corriente, completa con CIF)</td>
                    <td class="doc-checkbox">
                        <span class="{{ $isChecked('titular_constancia_fiscal') ? 'checkbox-checked' : 'checkbox-square' }}"></span>
                    </td>
                </tr>
                <tr>
                    <td class="doc-number">4</td>
                    <td class="doc-name">Comprobante de Domicilio Actual (Mes corriente, no mayor a 2 meses)</td>
                    <td class="doc-checkbox">
                        <span class="{{ $isChecked('titular_comprobante_domicilio_titular') ? 'checkbox-checked' : 'checkbox-square' }}"></span>
                    </td>
                </tr>
                <tr>
                    <td class="doc-number">5</td>
                    <td class="doc-name">Acta de Nacimiento (Legible)</td>
                    <td class="doc-checkbox">
                        <span class="{{ $isChecked('titular_acta_nacimiento') ? 'checkbox-checked' : 'checkbox-square' }}"></span>
                    </td>
                </tr>
                <tr>
                    <td class="doc-number">6</td>
                    <td class="doc-name">Acta de Matrimonio / Constancia de Régimen Matrimonial (Si aplica)</td>
                    <td class="doc-checkbox">
                        <span class="{{ $isChecked('titular_acta_matrimonio') ? 'checkbox-checked' : 'checkbox-square' }}"></span>
                    </td>
                </tr>
                <tr>
                    <td class="doc-number">7</td>
                    <td class="doc-name">Carátula de Estado de Cuenta Bancario con CLABE interbancaria</td>
                    <td class="doc-checkbox">
                        <span class="{{ $isChecked('titular_estado_cuenta_bancario') ? 'checkbox-checked' : 'checkbox-square' }}"></span>
                    </td>
                </tr>
                <tr>
                    <td class="doc-number">8</td>
                    <td class="doc-name">Carta Compromiso de Entrega de Documentación (Firmada)</td>
                    <td class="doc-checkbox">
                        <span class="{{ $isChecked('titular_carta_compromiso') ? 'checkbox-checked' : 'checkbox-square' }}"></span>
                    </td>
                </tr>
            </table>
        </div>

        {{-- DOCUMENTACIÓN PROPIEDAD --}}
        <div class="doc-section">
            <div class="doc-section-title">2. DOCUMENTACIÓN DE LA PROPIEDAD</div>
            
            <table class="doc-table">
                <tr>
                    <td class="doc-number">1</td>
                    <td class="doc-name">
                        Escritura Pública / Instrumento Notarial con sellos de Registro Público de la Propiedad
                    </td>
                    <td class="doc-checkbox">
                        <span class="{{ $isChecked('propiedad_instrumento_notarial') ? 'checkbox-checked' : 'checkbox-square' }}"></span>
                    </td>
                </tr>
                <tr>
                    <td class="doc-number">2</td>
                    <td class="doc-name">Boleta o Recibo de Pago Predial (Año en curso)</td>
                    <td class="doc-checkbox">
                        <span class="{{ $isChecked('propiedad_recibo_predial') ? 'checkbox-checked' : 'checkbox-square' }}"></span>
                    </td>
                </tr>
                <tr>
                    <td class="doc-number">3</td>
                    <td class="doc-name">Recibo de Agua Potable y/o Constancia de No Adeudo de Agua</td>
                    <td class="doc-checkbox">
                        <span class="{{ $isChecked('propiedad_recibo_agua') ? 'checkbox-checked' : 'checkbox-square' }}"></span>
                    </td>
                </tr>
                <tr>
                    <td class="doc-number">4</td>
                    <td class="doc-name">Recibo de CFE con datos fiscales al corriente</td>
                    <td class="doc-checkbox">
                        <span class="{{ $isChecked('propiedad_recibo_cfe') ? 'checkbox-checked' : 'checkbox-square' }}"></span>
                    </td>
                </tr>
                <tr>
                    <td class="doc-number">5</td>
                    <td class="doc-name">Carta de No Adeudo de Mantenimiento de Privada / Administración</td>
                    <td class="doc-checkbox">
                        <span class="{{ $isChecked('propiedad_mantenimiento') ? 'checkbox-checked' : 'checkbox-square' }}"></span>
                    </td>
                </tr>
            </table>
        </div>

        {{-- NOTAS IMPORTANTES --}}
        <div class="notes-section">
            <div class="note-item">
                <strong>📌 Entrega en Dos Momentos:</strong> Para iniciar la comercialización sólo se requieren los documentos básicos. El resto del expediente se integrará formalmente al momento de confirmarse el comprador.
            </div>
            <div class="note-item">
                <strong>📄 Formato:</strong> Todos los documentos deben enviarse escaneados en formato PDF legible, no fotos de celular.
            </div>
        </div>

        <div class="page-footer">
            <strong>XANTE &amp; VI, S.A.P.I. de C.V.</strong> — www.xante.mx — convenios@xante.mx
        </div>
        <div class="footer-bar"></div>

    </div>
</body>
</html>
