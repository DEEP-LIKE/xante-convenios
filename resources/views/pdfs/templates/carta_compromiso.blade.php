{{-- resources/views/pdfs/templates/carta_compromiso.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carta Compromiso de Entrega de Documentación</title>
    <style>
        @page {
            size: letter;
            margin: 1.5cm 2cm 1.2cm 2cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5pt;
            line-height: 1.45;
            color: #111;
        }

        .page-container {
            width: 100%;
        }

        /* ── HEADER ── */
        .page-header {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }

        .logo-container {
            display: table-cell;
            width: 130px;
            vertical-align: middle;
            text-align: left;
        }

        .logo {
            width: 100px;
            height: auto;
        }

        .header-divider {
            border: none;
            border-top: 2.5px solid #7DC142;
            margin: 4px 0 16px 0;
        }

        /* ── TÍTULO PRINCIPAL ── */
        .main-title {
            font-size: 11.5pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }

        /* ── FECHA ── */
        .date-line {
            text-align: right;
            font-size: 9.5pt;
            font-weight: bold;
            margin-bottom: 14px;
        }

        /* ── PÁRRAFOS ── */
        .paragraph {
            text-align: justify;
            margin-bottom: 10px;
            line-height: 1.45;
            font-size: 9.5pt;
        }

        /* ── HELPERS ── */
        .bold {
            font-weight: bold;
        }

        /* ── SECCIÓN DE FIRMA ── */
        .signature-section {
            margin-top: 22px;
            text-align: center;
        }

        .signature-intro {
            font-size: 10pt;
            font-weight: bold;
            text-align: left;
            margin-bottom: 8px;
        }

        .signature-line {
            border-top: 1.5px solid #000;
            width: 280px;
            margin: 38px auto 6px auto;
        }

        .signature-label {
            text-align: center;
            font-size: 9pt;
            font-weight: bold;
            line-height: 1.35;
        }

        .signature-name {
            font-weight: bold;
            font-size: 9.5pt;
            color: #000;
            margin-top: 2px;
        }

        /* ── DATOS DE CONTACTO ── */
        .contact-section {
            margin-top: 16px;
            padding-top: 10px;
            border-top: 1px dotted #ccc;
            font-size: 9pt;
        }

        .contact-title {
            font-weight: bold;
            font-size: 9.5pt;
            margin-bottom: 5px;
            color: #5B2D8E;
            text-align: left;
        }

        .contact-table {
            width: 100%;
            display: table;
        }

        .contact-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .contact-label {
            font-weight: bold;
            color: #333;
        }

        .contact-value {
            font-weight: bold;
            color: #000;
        }

        /* ── FOOTER ── */
        .page-footer {
            margin-top: 20px;
            border-top: 1px solid #ddd;
            padding-top: 6px;
        }

        .footer-company {
            font-size: 7.5pt;
            color: #444;
            line-height: 1.3;
            text-align: center;
        }

        .footer-bar {
            background-color: #7DC142;
            height: 6px;
            margin-top: 6px;
        }
    </style>
</head>
<body>
    <div class="page-container">

        {{-- LOGO + LÍNEA VERDE --}}
        <div class="page-header">
            <div class="logo-container">
                <img src="{{ $logo_path }}" alt="Xante Logo" class="logo">
            </div>
        </div>
        <hr class="header-divider">

        {{-- TÍTULO --}}
        <div class="main-title">
            Carta Compromiso de Entrega de Documentación
        </div>

        {{-- FECHA --}}
        <div class="date-line">
            Fecha: <span class="bold">{{ $day ?? now()->format('d') }} de {{ $month ?? 'enero' }} de {{ $year ?? now()->format('Y') }}.</span>
        </div>

        <div class="paragraph">
            Por medio de la presente, quien suscribe
            <span class="bold">{{ strtoupper($holder_name ?? '_________________________') }}</span>,
            en mi carácter de propietario titular y/o vendedor del inmueble ubicado en
            <span class="bold">{{ strtoupper($domicilio_convenio ?? '_________________________') }}</span>,
            del <span class="bold">CONJUNTO URBANO {{ strtoupper($property_full_community ?? '_________________________') }}</span>@if(!empty(trim($property_stage ?? ''))),
            <span class="bold">ETAPA {{ $property_stage }}</span>@endif.
            En el <span class="bold">MUNICIPIO DE {{ strtoupper($property_municipality ?? '_________________________') }}</span>,
            en el <span class="bold">ESTADO DE {{ strtoupper($property_state ?? '_________________________') }}</span>.
            Manifiesto mi conocimiento que para la correcta culminación del proceso de compraventa es
            indispensable la revisión y cotejo de mi documentación.
        </div>

        <div class="paragraph">
            En virtud de lo anterior, me comprometo a enviar a la empresa
            <span class="bold">XANTE &amp; VI, S.A.P.I de C.V.</span>,
            en un plazo máximo de <span class="bold">48 horas</span> contadas a partir de la notificación
            de la venta de mi inmueble, la documentación requerida de manera completa, legible y escaneada,
            quedando expresamente entendido que no se aceptarán fotografías de los documentos,
            únicamente formatos PDF enviados vía correo.
        </div>

        <div class="paragraph">
            Reconozco que el incumplimiento en la entrega de dicha documentación dentro del plazo
            establecido podrá retrasar o impedir la culminación del proceso de venta, sin que ello sea
            imputable a <span class="bold">XANTE</span>.
        </div>

        <div class="paragraph">
            Asimismo, declaro que la información y documentos proporcionados son auténticos y
            vigentes, y autorizo a <span class="bold">XANTE</span> a utilizarlos exclusivamente para los
            fines relacionados con la formalización de la operación de compraventa.
        </div>

        <div class="paragraph">
            Para constancia, firmo la presente carta compromiso en la fecha y lugar indicados.
        </div>

        {{-- SECCIÓN DE FIRMA --}}
        <div class="signature-section">
            <div class="signature-intro">Atentamente</div>

            <div class="signature-line"></div>

            <div class="signature-label">
                Nombre del propietario titular<br>
                Firma<br>
                <div class="signature-name">{{ strtoupper($holder_name ?? '') }}</div>
            </div>
        </div>

        {{-- DATOS DE CONTACTO --}}
        <div class="contact-section">
            <div class="contact-title">Datos de contacto</div>
            <div class="contact-table">
                <div class="contact-col">
                    <span class="contact-label">Teléfono:</span>
                    <span class="contact-value">{{ $holder_phone ?? 'N/A' }}</span>
                </div>
                <div class="contact-col">
                    <span class="contact-label">Correo:</span>
                    <span class="contact-value">{{ $holder_email ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        {{-- FOOTER --}}
        <div class="page-footer">
            <div class="footer-company">
                <strong>XANTE &amp; VI, S.A.P.I. de C.V.</strong><br>
                Avenida Vía Real, Local 1, Mz 16 Lt 1, Col. Real del Sol. Ojo de Agua, Tecámac, 55767, Estado de México.
            </div>
            <div class="footer-bar"></div>
        </div>

    </div>
</body>
</html>