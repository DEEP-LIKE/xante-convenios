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
            font-size: 11pt;
            line-height: 1.6;
            color: #000;
        }

        .page-container {
            width: 100%;
            max-width: 21.59cm;
            margin: 0 auto;
        }

        /* ── HEADER ── */
        .page-header {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .logo-container {
            display: table-cell;
            width: 130px;
            vertical-align: middle;
            text-align: left;
        }

        .logo {
            width: 110px;
            height: auto;
        }

        /* Línea decorativa verde (imita el estilo visual de Xante) */
        .header-divider {
            border: none;
            border-top: 3px solid #7DC142;
            margin: 6px 0 20px 0;
        }

        /* ── TÍTULO PRINCIPAL ── */
        .main-title {
            font-size: 13pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 30px;
            letter-spacing: 0.5px;
        }

        /* ── FECHA ── */
        .date-line {
            text-align: right;
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 30px;
        }

        .date-blank {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 30px;
        }

        .date-blank-long {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 120px;
        }

        /* ── PÁRRAFOS ── */
        .paragraph {
            text-align: justify;
            margin-bottom: 20px;
            line-height: 1.7;
        }

        /* ── BLANCOS INLINE ── */
        .blank {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 200px;
        }

        .blank-short {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 60px;
        }

        .blank-medium {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 120px;
        }

        /* ── BOLD / UPPERCASE helpers ── */
        .bold {
            font-weight: bold;
        }

        .upper {
            text-transform: uppercase;
        }

        /* ── SECCIÓN DE FIRMA ── */
        .signature-section {
            margin-top: 50px;
            page-break-inside: avoid;
        }

        .signature-line {
            border-top: 2px solid #000;
            width: 60%;
            margin: 50px auto 10px auto;
        }

        .signature-label {
            text-align: center;
            font-size: 10pt;
            font-weight: bold;
            line-height: 1.5;
        }

        /* ── DATOS DE CONTACTO ── */
        .contact-section {
            margin-top: 30px;
        }

        .contact-title {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 12px;
        }

        .contact-field {
            margin-bottom: 12px;
            font-size: 11pt;
        }

        .contact-field-label {
            font-weight: bold;
            display: inline-block;
            min-width: 80px;
        }

        .contact-field-value {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 250px;
        }

        /* ── FOOTER ── */
        .page-footer {
            margin-top: 40px;
            border-top: 1px solid #ccc;
            padding-top: 8px;
        }

        .footer-table {
            display: table;
            width: 100%;
        }

        .footer-left {
            display: table-cell;
            vertical-align: middle;
            text-align: left;
        }

        .footer-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
        }

        .footer-logo-text {
            font-size: 14pt;
            font-weight: bold;
            color: #5B2D8E;
        }

        .footer-logo-text span {
            color: #7DC142;
        }

        .footer-company {
            font-size: 7.5pt;
            color: #333;
            line-height: 1.4;
            text-align: center;
        }

        .footer-social {
            font-size: 8pt;
            color: #333;
            text-align: right;
        }

        /* ── BARRA INFERIOR VERDE ── */
        .footer-bar {
            background-color: #7DC142;
            height: 8px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    {{-- PÁGINA 1 --}}
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
            Fecha: <span class="date-blank">{{ $day ?? '____' }}</span> de
            <span class="date-blank-long">{{ $month ?? '_______________' }}</span> de
            <span class="bold">{{ $year ?? '2026' }}.</span>
        </div>

        <div class="paragraph">
            Por medio de la presente, quien suscribe
            <span class="blank">{{ strtoupper($holder_name ?? '') }}</span>,
            en mi carácter de propietario titular y/o vendedor del inmueble ubicado en
            <span class="blank bold">{{ strtoupper($domicilio_convenio ?? '') }}</span>,
            del <span class="bold">CONJUNTO URBANO
            <span class="blank">{{ strtoupper($property_full_community ?? '') }}</span>,
            ETAPA <span class="blank-short">{{ $property_stage ?? '' }}</span></span>.
            En el <span class="bold">MUNICIPIO DE
            <span class="blank-medium">{{ strtoupper($property_municipality ?? '') }}</span></span>,
            en el <span class="bold">ESTADO DE
            <span class="blank-medium">{{ strtoupper($property_state ?? '') }}</span></span>.
            Manifiesto mi conocimiento que para la correcta culminación del proceso de compraventa es
            indispensable la revisión y coteja de mi documentación.
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
        <div class="page-footer">
            <div class="footer-company">
                <strong>XANTE &amp; VI, S.A.P.I. de C.V.</strong><br>
                Avenida Vía Real, Local 1, Mz 16 Lt 1, Col. Real del Sol. Ojo de Agua, Tecámac, 55767, Estado de México.
            </div>
        </div>
        <div class="footer-bar"></div>

    </div>

    <div style="page-break-before: always;"></div>
    <div class="page-container">

        <div class="page-header">
            <div class="logo-container">
                <img src="{{ $logo_path }}" alt="Xante Logo" class="logo">
            </div>
        </div>
        <hr class="header-divider">

        <div class="signature-section">
            <div style="margin-bottom: 8px; font-size: 11pt; font-weight: bold;">Atentamente</div>

            <div class="signature-line"></div>

            <div class="signature-label">
                Nombre del propietario titular<br>
                Firma<br>
                <span style="font-weight: normal; font-size: 10pt;">{{ strtoupper($holder_name ?? '') }}</span>
            </div>
        </div>

        <div class="contact-section">
            <div class="contact-title">Datos de contacto</div>

            <div class="contact-field">
                <span class="contact-field-label">Teléfono:</span>
                <span class="contact-field-value">{{ $holder_phone ?? '' }}</span>
            </div>

            <div class="contact-field">
                <span class="contact-field-label">Correo:</span>
                <span class="contact-field-value">{{ $holder_email ?? '' }}</span>
            </div>
        </div>
        <div class="page-footer" style="position: absolute; bottom: 40px; left: 40px; right: 40px;">
            <div class="footer-table">
                <div class="footer-left">
                    <div class="footer-logo-text"><span>Xante</span>.mx</div>
                </div>
                <div class="footer-right">
                    <div class="footer-company">
                        <strong>XANTE &amp; VI, S.A.P.I. de C.V.</strong><br>
                        Avenida Vía Real, Local 1, Mz 16 Lt 1, Col. Real del Sol.<br>
                        Ojo de Agua, Tecámac, 55767, Estado de México.
                    </div>
                </div>
            </div>
            <div class="footer-bar"></div>
        </div>

    </div>
</body>
</html>