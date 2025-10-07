{{-- resources/views/pdfs/templates/acuerdo_promocion.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acuerdo de Promoción y Comercialización del Inmueble</title>
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
            line-height: 1.5;
            color: #000;
        }
        
        .page-container {
            width: 100%;
            max-width: 21.59cm;
            margin: 0 auto;
        }
        
        /* HEADER */
        .page-header {
            margin-bottom: 20px;
        }
        
        .header-content {
            display: table;
            width: 100%;
        }
        
        .logo-container {
            display: table-cell;
            width: 120px;
            vertical-align: middle;
            text-align: left;
        }
        
        .logo {
            width: 100px;
            height: auto;
        }
        
        .title-container {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
        
        .page-number {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 10pt;
            font-weight: bold;
        }
        
        .website {
            font-size: 9pt;
            color: #000;
            margin-bottom: 10px;
        }
        
        .main-title {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 25px;
            line-height: 1.3;
        }
        
        /* FECHA Y UBICACIÓN */
        .date-location {
            text-align: left;
            margin-bottom: 25px;
            font-size: 11pt;
        }
        
        /* SUBTÍTULO */
        .subtitle {
            font-weight: bold;
            text-transform: uppercase;
            margin: 20px 0;
            text-align: center;
        }
        
        .seller-name {
            text-decoration: underline;
        }
        
        /* PÁRRAFOS */
        .paragraph {
            text-align: justify;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        /* PRECIO BOX */
        .price-box {
            text-align: center;
            margin: 20px 0;
            font-size: 11pt;
        }
        
        /* DATOS BANCARIOS */
        .bank-data {
            margin: 20px 0;
            padding: 12px;
            border: 1px solid #000;
            background-color: #f5f5f5;
        }
        
        .bank-data .line {
            margin-bottom: 8px;
            font-size: 10pt;
        }
        
        .bank-label {
            font-weight: bold;
            display: inline-block;
            min-width: 120px;
        }
        
        /* LISTAS */
        .obligations-list {
            margin: 15px 0 15px 25px;
            list-style-type: disc;
        }
        
        .obligations-list li {
            margin-bottom: 12px;
            text-align: justify;
            line-height: 1.5;
        }
        
        .obligations-list ul {
            margin: 8px 0 8px 25px;
            list-style-type: circle;
        }
        
        .obligations-list ul li {
            margin-bottom: 6px;
        }
        
        /* FIRMAS */
        .signatures-section {
            margin-top: 60px;
            page-break-inside: avoid;
        }
        
        .signature-block {
            margin: 40px 0;
        }
        
        .signature-label {
            font-weight: bold;
            margin-bottom: 50px;
        }
        
        .signature-line {
            border-top: 2px solid #000;
            width: 300px;
            margin: 0 auto 8px auto;
        }
        
        .signature-name {
            text-align: center;
            font-weight: bold;
        }
        
        /* AVISO DE PRIVACIDAD */
        .privacy-notice {
            margin-top: 30px;
            font-size: 9pt;
            text-align: center;
            line-height: 1.4;
        }
        
        .privacy-link {
            color: #0066cc;
            text-decoration: none;
        }
        
        /* SALTO DE PÁGINA */
        .page-break {
            page-break-before: always;
        }
        
        /* UTILIDADES */
        .bold {
            font-weight: bold;
        }
        
        .underline {
            text-decoration: underline;
        }
        
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    {{-- PÁGINA 1 --}}
    <div class="page-container">
        <div class="page-number">1</div>
        
        <div class="page-header">
            <div class="header-content">
                <div class="logo-container">
                    <img src="{{ $logo_path }}" alt="Xante Logo" class="logo">
                </div>
                <div class="title-container">
                    <div class="website">www.xante.mx</div>
                    <div class="main-title">
                        ACUERDO DE PROMOCIÓN Y COMERCIALIZACIÓN DEL<br>
                        INMUEBLE
                    </div>
                </div>
            </div>
        </div>

        <div class="date-location">
            Estado de <span class="bold">{{ $property_state ?? '_________' }}</span> a 
            <span class="bold underline">{{ $day ?? '__' }}</span> de 
            <span class="bold underline">{{ $month ?? '__________' }}</span> de 
            <span class="bold underline">{{ $year ?? '2025' }}</span>
        </div>

        <div class="subtitle">
            ACUERDO DE PROMOCIÓN INMOBILIARIA ENTRE<br>
            <span class="seller-name">{{ strtoupper($holder_name ?? '_________________________') }}</span> COMO EL VENDEDOR Y XANTE & VI, S.A.P.I. DE C.V
        </div>

        <div class="paragraph">
            Por medio de esta carta el vendedor(es) <span class="bold">{{ strtoupper($holder_name ?? '_________________________') }}</span> autoriza a XANTE, de manera no exclusiva, a realizar la promoción y
            publicidad que considere necesaria para lograr la venta del inmueble (en adelante el "Inmueble") CON EL
            NÚMERO INTERIOR "<span class="bold">{{ $property_interior_number ?? '__' }}</span>", PERTENECIENTE AL RÉGIMEN DE PROPIEDAD EN
            CONDOMINIO DENOMINADO "<span class="bold">{{ strtoupper($property_community ?? 'PRIVADA _________') }}</span>",
        </div>

        <div class="paragraph">
            CONSTITUIDO SOBRE EL LOTE "<span class="bold">{{ $property_lot ?? '__' }}</span>", MANZANA "<span class="bold">{{ $property_block ?? 'M' }}</span>" ETAPA <span class="bold">{{ $property_stage ?? '__' }}</span>, DE LA
            COMUNIDAD DENOMINADA "<span class="bold">{{ strtoupper($property_full_community ?? '_________') }}</span>", EN EL
            MUNICIPIO DE <span class="bold">{{ strtoupper($property_municipality ?? '_________') }}</span>, ESTADO DE <span class="bold">{{ strtoupper($property_state ?? '_________') }}</span>.
        </div>

        <div class="price-box">
            El precio de promoción del inmueble será de: <span class="bold">${{ number_format($precio_promocion ?? 0, 2, '.', ',') }}</span><br>
            (<span class="bold">{{ strtoupper($precio_promocion_letras ?? '_________________________') }} PESOS 00/100 M.N.</span>)
        </div>

        <div class="paragraph">
            XANTE registrará a los prospectos interesados en el Inmueble por medio de correo electrónico y/o documento
            firmado físico o digitalmente.
        </div>

        <div class="paragraph">
            Se reconocerá como Prospecto XANTE al propio prospecto, su esposo o esposa, su concubino o concubina,
            sus hijos, su familia ascendiente y descendiente y/o el representante legal del mismo.
        </div>

        <div class="paragraph">
            El VENDEDOR(ES) expresamente reconoce que la comisión que cubrirá a XANTE por la promoción
            inmobiliaria será la cantidad equivalente al <span class="bold">{{ $porcentaje_comision ?? '6.5' }}% {{ $porcentaje_comision_letras ?? 'seis punto cinco por ciento' }}</span> más el impuesto al valor
            agregado (IVA) sobre el precio por la venta del Inmueble. El cálculo del IVA es sobre el monto de la comisión.
        </div>

        <div class="paragraph">
            El pago por concepto de comisión a XANTE sólo se pagará por EL VENDEDOR(ES) en caso de cerrarse la
            operación con un prospecto XANTE. El pago de dicha comisión se realizará de la siguiente forma:
        </div>

        <div class="paragraph">
            Al momento de la formalización de la escritura pública se pagará el 100% de la comisión lo que equivale al
            <span class="bold">{{ $porcentaje_comision ?? '6.5' }}% ({{ ucfirst($porcentaje_comision_letras ?? 'Seis punto cinco por ciento') }})</span> más el Impuesto al Valor Agregado.
        </div>

        <div class="paragraph">
            De no firmar contrato o promesa de compraventa y no existir un anticipo entre el comprador y el vendedor,
            la comisión de XANTE será pagada en su totalidad al momento de la escrituración cuando EL VENDEDOR(ES)
            reciban el pago total del Inmueble.
        </div>

        <div class="paragraph">
            El VENDEDOR(ES) se compromete a hacer los pagos correspondientes dentro de las 24 (veinticuatro) horas
            siguientes a la fecha de condición de pago, mediante depósito o transferencia bancaria a la cuenta:
        </div>
    </div>

    {{-- PÁGINA 2 --}}
    <div class="page-break"></div>
    <div class="page-container">
        <div class="page-number">2</div>
        
        <div class="page-header">
            <div class="header-content">
                <div class="logo-container">
                    <img src="{{ $logo_path }}" alt="Xante Logo" class="logo">
                </div>
                <div class="title-container">
                    <div class="website">www.xante.mx</div>
                    <div class="main-title">
                        ACUERDO DE PROMOCIÓN Y COMERCIALIZACIÓN DEL<br>
                        INMUEBLE
                    </div>
                </div>
            </div>
        </div>

        <div class="bank-data">
            <div class="line"><span class="bank-label">Nombre titular:</span> <span class="bold">XANTE & VI, S.A.P.I. DE C.V.</span></div>
            <div class="line"><span class="bank-label">Banco:</span> <span class="bold">{{ $bank_name ?? 'BBVA' }}</span></div>
            <div class="line"><span class="bank-label">Cuenta:</span> <span class="bold underline">{{ $bank_account ?? '___________________' }}</span></div>
            <div class="line"><span class="bank-label">CLABE:</span> <span class="bold underline">{{ $bank_clabe ?? '___________________' }}</span></div>
        </div>

        <div class="paragraph" style="margin-top: 20px;">
            <span class="bold">El VENDEDOR(ES) acepta y reconoce lo siguiente:</span>
        </div>

        <ul class="obligations-list">
            <li>Que soy el único y legítimo propietario del inmueble.</li>
            
            <li>
                Me comprometo a entregar los siguientes accesos y llaves correspondientes a la vivienda:
                <ul>
                    <li>Llave de la cerradura principal (acceso a la vivienda)</li>
                    <li>Acceso vehicular y peatonal a la privada</li>
                    <li>Acceso vehicular y peatonal al fraccionamiento</li>
                </ul>
                En caso de no contar con alguno de los accesos mencionados, me obligo a realizar las gestiones
                necesarias para obtenerlos y entregarlos a más tardar en la fecha de entrega de la vivienda, a fin de
                garantizar el acceso pleno e inmediato al comprador.
            </li>
            
            <li>
                El inmueble se encuentra en condiciones físicas y jurídicas para venderse y disponerse, se encuentra en
                regla y cuenta con todo lo necesario para que un comprador pueda realizar un trámite de escrituración.
            </li>
            
            <li>
                Que libero de cualquier responsabilidad a XANTE en cuanto a garantías y vicios ocultos en el inmueble,
                objeto de la presente operación, toda vez, que está catalogado como "usado".
            </li>
            
            <li>
                Que entregaré todos los documentos del Inmueble que solicite XANTE, durante el periodo de promoción,
                al momento de la primera visita por un prospecto XANTE, al momento de una oferta de un prospecto
                XANTE o en cualquier otro momento que lo solicite XANTE.
            </li>
            
            <li>
                Que celebrar cualquier tipo de acuerdo con el prospecto XANTE, sin notificarlo a XANTE, constituye un
                acto de mala fe, a través del cual, actuando con dolo, obtendría un lucro indebido, al no pagar la comisión
                que le corresponde a XANTE.
            </li>
            
            <li>
                Que negarme a cubrir la comisión XANTE en tiempo y forma motivará el pago de un interés igual al 10%
                (diez por ciento) mensual sobre la comisión, además del pago de gastos y costas en caso de que se me
                requiera judicialmente.
            </li>
            
            <li>
                Una vez que la vivienda en promoción tenga el primer apartado, el propietario se obliga a no promocionar
                por ningún medio la propiedad.
            </li>
        </ul>
    </div>

    {{-- PÁGINA 3 --}}
    <div class="page-break"></div>
    <div class="page-container">
        <div class="page-number">3</div>
        
        <div class="page-header">
            <div class="header-content">
                <div class="logo-container">
                    <img src="{{ $logo_path }}" alt="Xante Logo" class="logo">
                </div>
                <div class="title-container">
                    <div class="website">www.xante.mx</div>
                    <div class="main-title">
                        ACUERDO DE PROMOCIÓN Y COMERCIALIZACIÓN DEL<br>
                        INMUEBLE
                    </div>
                </div>
            </div>
        </div>

        <ul class="obligations-list" style="margin-top: 20px;">
            <li>
                Una vez que la vivienda en promoción tenga el primer apartado, el propietario se obliga a entregar toda la
                documentación relativa al inmueble como es: boleta predial, pago por servicios de agua, constancia de no
                adeudo de cuotas de mantenimiento, recibo de luz pagados en el mes en curso.
            </li>
            
            <li>
                Con datos fiscales para validar exención de ISR, así como la escritura original del inmueble y generales
                del titular actualizados del mes corriente. En caso de existir gastos adicionales como certificaciones de no
                adeudo, controles de acceso, estoy de acuerdo que deberán ser cubiertos por mi parte.
            </li>
            
            <li>
                El apartado de la vivienda que se pretende enajenar será depositado a la cuenta de XANTE, toda vez que
                no forma parte del precio.
            </li>
            
            <li>
                El tiempo promedio de escrituración de la propiedad es de un aproximado de 10 semanas, siempre
                dependerá del tipo de crédito.
            </li>
        </ul>

        <div class="paragraph" style="margin-top: 25px;">
            XANTE dará tratamiento a este dato personal en términos del Aviso de Privacidad que se encuentra en el
            portal web de XANTE.
        </div>

        <div class="paragraph" style="margin-top: 20px;">
            En cumplimiento a lo establecido en las leyes, se hace constar que la información establecida en este
            acuerdo, así como la documentación entregada entre ambas partes es confidencial y/o privilegiada y por
            lo tanto su uso está destinado exclusivamente para los fines de este y para la promoción de los
            inmuebles.
        </div>

        {{-- FIRMAS --}}
        <div class="signatures-section">
            <div class="signature-block">
                <div class="signature-label">EL VENDEDOR(ES)</div>
                <div class="signature-line"></div>
                <div class="signature-name">{{ strtoupper($holder_name ?? 'NOMBRE Y FIRMA VENDEDOR') }}</div>
            </div>

            <div class="signature-block" style="margin-top: 50px;">
                <div class="signature-label">XANTE & VI, S.A.P.I. DE C.V.</div>
                <div class="signature-line"></div>
                <div class="signature-name">C.P. CÉSAR RODRÍGUEZ REYES</div>
            </div>
        </div>

        <div class="privacy-notice">
            Las partes se comprometen a guardar absoluta discreción de la información confidencial. Estando a la vista<br>
            para el vendedor el aviso de privacidad de XANTE en:<br>
            <a href="https://xante.mx/assets/pdf/AVISO%20DE%20PRIVACIDAD%20PROMOTORA.pdf" class="privacy-link">
                https://xante.mx/assets/pdf/AVISO%20DE%20PRIVACIDAD%20PROMOTORA.pdf
            </a>
        </div>
    </div>
</body>
</html>