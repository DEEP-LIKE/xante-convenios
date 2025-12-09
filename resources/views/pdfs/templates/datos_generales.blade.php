{{-- resources/views/pdfs/templates/datos_generales.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datos Generales - Fase I</title>
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
            font-size: 9pt;
            line-height: 1.2;
            color: #000;
        }
        
        .page-container {
            width: 100%;
            max-width: 21.59cm;
            margin: 0 auto;
        }
        
        /* HEADER CON LOGO */
        .header {
            display: table;
            width: 100%;
        }
        
        .logo-container {
            display: table-cell;
            width: 120px;
            vertical-align: middle;
        }
        
        .logo {
            width: 100px;
            height: auto;
        }
        
        .header-info {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
        }
        
        .header-box {
            display: inline-block;
            border: 1px solid #000;
            padding: 3px 8px;
            margin-left: 5px;
            min-width: 80px;
        }
        
        .header-label {
            font-size: 8pt;
            font-weight: bold;
        }
        
        .header-value {
            font-size: 9pt;
            border-bottom: 1px solid #000;
            min-height: 16px;
            display: block;
            margin-top: 2px;
        }
        
        /* TÍTULO PRINCIPAL */
        .main-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        
        /* SECCIONES */
        .section {
            margin-bottom: 18px;
            page-break-inside: avoid;
        }
        
        .section-title {
            background-color: #A8D08D;
            font-weight: bold;
            font-size: 9pt;
            padding: 4px 8px;
            margin-bottom: 8px;
            text-transform: uppercase;
            border: 1px solid #000;
        }
        
        /* TABLA DE DATOS */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        .data-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
            font-size: 8.5pt;
        }
        
        .data-label {
            font-weight: bold;
            background-color: #f5f5f5;
            width: 28%;
            white-space: nowrap;
        }
        
        .data-value {
            width: 72%;
            min-height: 18px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        /* Para valores largos que necesitan ajustarse */
        .data-value-long {
            font-size: 7.5pt;
            line-height: 1.3;
        }
        
        /* GRID DE 2 COLUMNAS */
        .two-column-grid {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        
        .column-50 {
            display: table-cell;
            width: 50%;
            padding-right: 4px;
            vertical-align: top;
        }
        
        .column-50:last-child {
            padding-right: 0;
            padding-left: 4px;
        }
        
        /* SECCIÓN DE CONTACTOS */
        .contact-section {
            margin-bottom: 18px;
        }
        
        .contact-grid {
            display: table;
            width: 100%;
        }
        
        .contact-column {
            display: table-cell;
            width: 50%;
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
        }
        
        .contact-column:first-child {
            border-right: none;
        }
        
        .contact-field {
            margin-bottom: 6px;
        }
        
        .contact-label {
            font-weight: bold;
            font-size: 8pt;
            display: block;
            margin-bottom: 2px;
        }
        
        .contact-value {
            border-bottom: 1px solid #000;
            min-height: 16px;
            display: block;
            padding: 2px 0;
        }
        
        /* FIRMA */
        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        
        .signature-line {
            border-top: 2px solid #000;
            width: 60%;
            margin: 50px auto 8px auto;
        }
        
        .signature-label {
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
        }
        
        /* UTILIDADES */
        .text-uppercase {
            text-transform: uppercase;
        }
        
        .text-center {
            text-align: center;
        }
        
        .font-bold {
            font-weight: bold;
        }
        
        /* Ajuste para textos muy largos */
        .truncate-text {
            display: block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="page-container">
        
        {{-- HEADER CON LOGO --}}
        <div class="header">
            <div class="logo-container">
                <img src="{{ $logo_path }}" alt="Xante Logo" class="logo">
            </div>
            <div class="header-info">
                <div class="header-box">
                    <span class="header-label">ID Xante</span>
                    <span class="header-value">{{ $xante_id ?? '___________' }}</span>
                </div>
                <div class="header-box">
                    <span class="header-label">Fecha</span>
                    <span class="header-value">{{ $fecha_actual ?? '___________' }}</span>
                </div>
            </div>
        </div>

        {{-- TÍTULO PRINCIPAL --}}
        <div class="main-title">
            DATOS GENERALES - "FASE I"
        </div>

        {{-- SECCIÓN: DATOS PERSONALES TITULAR --}}
        <div class="section">
            <div class="section-title">DATOS PERSONALES TITULAR:</div>
            
            <table class="data-table">
                <tr>
                    <td class="data-label">Nombre Cliente</td>
                    <td class="data-value {{ strlen($holder_name ?? '') > 40 ? 'data-value-long' : '' }}">
                        {{ $holder_name ?? '' }}
                    </td>
                    <td class="data-label">Entrega expediente</td>
                    <td class="data-value">{{ $holder_delivery_file ?? '' }}</td>
                </tr>
                <tr>
                    <td class="data-label">Fecha de Nacimiento</td>
                    <td class="data-value">{{ $holder_birthdate ? (strlen($holder_birthdate) > 10 ? substr($holder_birthdate, 0, 10) : $holder_birthdate) : '' }}</td>
                    <td class="data-label">Estado civil</td>
                    <td class="data-value">{{ ucfirst(str_replace('_', ' ', $holder_civil_status ?? '')) }}</td>
                </tr>
                <tr>
                    <td class="data-label">CURP</td>
                    <td class="data-value">{{ $holder_curp ?? '' }}</td>
                    <td class="data-label">Régimen Fiscal</td>
                    <td class="data-value">{{ $holder_regime_type ?? '' }}</td>
                </tr>
                <tr>
                    <td class="data-label">RFC</td>
                    <td class="data-value">{{ $holder_rfc ?? '' }}</td>
                    <td class="data-label">Ocupación</td>
                    <td class="data-value">{{ $holder_occupation ?? '' }}</td>
                </tr>
                <tr>
                    <td class="data-label">Correo electrónico</td>
                    <td class="data-value {{ strlen($holder_email ?? '') > 35 ? 'data-value-long' : '' }}">
                        {{ $holder_email ?? '' }}
                    </td>
                    <td class="data-label">Tel. oficina</td>
                    <td class="data-value">{{ $holder_office_phone ?? '' }}</td>
                </tr>
                <tr>
                    <td class="data-label">Núm. Celular</td>
                    <td class="data-value">{{ $holder_phone ?? '' }}</td>
                    <td class="data-label">Tel. Contacto Adic.</td>
                    <td class="data-value">{{ $holder_additional_contact_phone ?? '' }}</td>
                </tr>
            </table>

            <table class="data-table">
                <tr>
                    <td class="data-label" style="width: 14%;">Domicilio Actual</td>
                    <td class="data-value {{ strlen($holder_current_address ?? '') > 60 ? 'data-value-long' : '' }}" style="width: 86%;" colspan="3">
                        {{ $holder_current_address ?? '' }}
                    </td>
                </tr>
                <tr>
                    <td class="data-label">Colonia</td>
                    <td class="data-value">{{ $holder_neighborhood ?? '' }}</td>
                    <td class="data-label">C.P.</td>
                    <td class="data-value">{{ $holder_postal_code ?? '' }}</td>
                </tr>
                <tr>
                    <td class="data-label">Municipio - Alcaldía</td>
                    <td class="data-value">{{ $holder_municipality ?? '' }}</td>
                    <td class="data-label">Estado</td>
                    <td class="data-value">{{ $holder_state ?? '' }}</td>
                </tr>
            </table>
        </div>

        {{-- SECCIÓN: DATOS PERSONALES COACREDITADO/CÓNYUGE (Solo si fue seleccionado) --}}
        @if(!empty($wizardData['has_co_borrower']) && $wizardData['has_co_borrower'])
        <div class="section">
            <div class="section-title">DATOS PERSONALES COACREDITADO / CÓNYUGE:</div>
            
            <table class="data-table">
                <tr>
                    <td class="data-label">Nombre Cliente</td>
                    <td class="data-value {{ strlen($spouse_name ?? '') > 40 ? 'data-value-long' : '' }}">
                        {{ $spouse_name ?? '' }}
                    </td>
                    <td class="data-label">Entrega expediente</td>
                    <td class="data-value">{{ $spouse_delivery_file ?? '' }}</td>
                </tr>
                <tr>
                    <td class="data-label">Fecha de Nacimiento</td>
                    <td class="data-value">{{ $spouse_birthdate ? (strlen($spouse_birthdate) > 10 ? substr($spouse_birthdate, 0, 10) : $spouse_birthdate) : '' }}</td>
                    <td class="data-label">Estado civil</td>
                    <td class="data-value">{{ ucfirst(str_replace('_', ' ', $spouse_civil_status ?? '')) }}</td>
                </tr>
                <tr>
                    <td class="data-label">CURP</td>
                    <td class="data-value">{{ $spouse_curp ?? '' }}</td>
                    <td class="data-label">Régimen Fiscal</td>
                    <td class="data-value">{{ $spouse_regime_type ?? '' }}</td>
                </tr>
                <tr>
                    <td class="data-label">RFC</td>
                    <td class="data-value">{{ $spouse_rfc ?? '' }}</td>
                    <td class="data-label">Ocupación</td>
                    <td class="data-value">{{ $spouse_occupation ?? '' }}</td>
                </tr>
                <tr>
                    <td class="data-label">Correo electrónico</td>
                    <td class="data-value {{ strlen($spouse_email ?? '') > 35 ? 'data-value-long' : '' }}">
                        {{ $spouse_email ?? '' }}
                    </td>
                    <td class="data-label">Tel. oficina</td>
                    <td class="data-value">{{ $spouse_office_phone ?? '' }}</td>
                </tr>
                <tr>
                    <td class="data-label">Núm. Celular</td>
                    <td class="data-value">{{ $spouse_phone ?? '' }}</td>
                    <td class="data-label">Tel. Contacto Adic.</td>
                    <td class="data-value">{{ $spouse_additional_contact_phone ?? '' }}</td>
                </tr>
            </table>

            <table class="data-table">
                <tr>
                    <td class="data-label" style="width: 14%;">Domicilio Actual</td>
                    <td class="data-value {{ strlen($spouse_current_address ?? '') > 60 ? 'data-value-long' : '' }}" style="width: 86%;" colspan="3">
                        {{ $spouse_current_address ?? '' }}
                    </td>
                </tr>
                <tr>
                    <td class="data-label">Colonia</td>
                    <td class="data-value">{{ $spouse_neighborhood ?? '' }}</td>
                    <td class="data-label">C.P.</td>
                    <td class="data-value">{{ $spouse_postal_code ?? '' }}</td>
                </tr>
                <tr>
                    <td class="data-label">Municipio - Alcaldía</td>
                    <td class="data-value">{{ $spouse_municipality ?? '' }}</td>
                    <td class="data-label">Estado</td>
                    <td class="data-value">{{ $spouse_state ?? '' }}</td>
                </tr>
            </table>
        </div>
        @endif

        {{-- SECCIÓN: CONTACTO AC Y/O PRESIDENTE DE PRIVADA --}}
        <div class="contact-section">
            <div class="section-title">CONTACTO AC Y/O PRESIDENTE DE PRIVADA</div>
            
            <div class="contact-grid">
                <div class="contact-column">
                    <div class="contact-field">
                        <span class="contact-label">NOMBRE AC</span>
                        <span class="contact-value">{{ $ac_name ?? '' }}</span>
                    </div>
                    <div class="contact-field">
                        <span class="contact-label">Núm. Celular</span>
                        <span class="contact-value">{{ $ac_phone ?? '' }}</span>
                    </div>
                    <div class="contact-field">
                        <span class="contact-label">CUOTA</span>
                        <span class="contact-value">
                            @if(isset($ac_quota) && $ac_quota)
                                ${{ number_format($ac_quota, 2) }}
                            @endif
                        </span>
                    </div>
                </div>
                
                <div class="contact-column">
                    <div class="contact-field">
                        <span class="contact-label">PRESIDENTE PRIVADA</span>
                        <span class="contact-value">{{ $private_president_name ?? '' }}</span>
                    </div>
                    <div class="contact-field">
                        <span class="contact-label">Núm. Celular</span>
                        <span class="contact-value">{{ $private_president_phone ?? '' }}</span>
                    </div>
                    <div class="contact-field">
                        <span class="contact-label">CUOTA</span>
                        <span class="contact-value">
                            @if(isset($private_president_quota) && $private_president_quota)
                                ${{ number_format($private_president_quota, 2) }}
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECCIÓN DE FIRMA --}}
        <div class="signature-section">
            <div class="signature-line"></div>
            <div class="signature-label">
                Nombre y firma de conformidad y enterado<br>
                Vendedor
            </div>
        </div>

    </div>
</body>
</html>