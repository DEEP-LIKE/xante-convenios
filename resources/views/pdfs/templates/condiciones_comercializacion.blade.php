{{-- resources/views/pdfs/templates/condiciones_comercializacion.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Condiciones para Comercialización Xante</title>
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
            display: table;
            width: 100%;
            margin-bottom: 20px;
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
            vertical-align: top;
            padding-left: 15px;
        }
        
        .property-data {
            margin-bottom: 3px;
            font-size: 9pt;
        }
        
        .property-label {
            font-weight: bold;
            display: inline-block;
            min-width: 110px;
        }
        
        .property-value {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 200px;
        }
        
        .date-box {
            text-align: right;
            margin-top: 10px;
            font-size: 9pt;
        }
        
        .date-value {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 30px;
        }
        
        /* TÍTULO */
        .main-title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 25px;
            background-color: #A8D08D;
            padding: 8px;
            border: 1px solid #000;
        }
        
        /* CONDICIONES */
        .condition {
            margin-bottom: 15px;
            text-align: justify;
            line-height: 1.5;
        }
        
        .condition-number {
            font-weight: bold;
            display: inline;
        }
        
        /* OPCIONES DE VISITA */
        .options-section {
            margin: 15px 0 15px 20px;
        }
        
        .option-label {
            font-weight: bold;
            margin-bottom: 10px;
            text-decoration: underline;
        }
        
        .options-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .option-box {
            display: table-cell;
            width: 33.33%;
            border: 1px solid #000;
            padding: 10px;
            vertical-align: top;
            text-align: center;
        }
        
        .option-letter {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .option-title {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 9pt;
        }
        
        .option-description {
            font-size: 8pt;
            font-style: italic;
            margin-top: 5px;
            line-height: 1.3;
        }
        
        .option-note {
            font-size: 7.5pt;
            margin-top: 8px;
            line-height: 1.2;
        }
        
        .hours-input {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 40px;
            margin: 0 3px;
        }
        
        /* SUB-LISTAS */
        .sub-list {
            margin: 8px 0 8px 30px;
            list-style-type: lower-alpha;
        }
        
        .sub-list li {
            margin-bottom: 8px;
            text-align: justify;
            line-height: 1.4;
        }
        
        /* ANEXO */
        .anexo-section {
            margin: 20px 0;
        }
        
        .anexo-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .anexo-list {
            margin-left: 20px;
            list-style-type: disc;
        }
        
        .anexo-list li {
            margin-bottom: 8px;
            text-align: justify;
            line-height: 1.4;
        }
        
        /* FIRMA */
        .signature-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }
        
        .signature-line {
            border-top: 2px solid #000;
            width: 60%;
            margin: 40px auto 8px auto;
        }
        
        .signature-label {
            text-align: center;
            font-size: 9pt;
            font-weight: bold;
        }
        
        /* EJEMPLO DE FOTOS */
        .example-section {
            margin-top: 25px;
            font-weight: bold;
            font-size: 10pt;
        }
        
        /* UTILIDADES */
        .bold {
            font-weight: bold;
        }
        
        .italic {
            font-style: italic;
        }
        
        .underline {
            text-decoration: underline;
        }
        
        /* GALERÍA DE IMÁGENES */
        .galeria-imagenes {
            margin-top: 15px;
            width: 100%;
        }
        
        .fila-imagenes {
            display: table;
            width: 100%;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        
        .contenedor-imagen {
            display: table-cell;
            width: 33.33%;
            padding: 5px;
            text-align: center;
            vertical-align: top;
        }
        
        .imagen-ejemplo {
            width: 100%;
            aspect-ratio: 9 / 16;
            object-fit: cover;
            border: 1px solid #ccc;
        }
        
        .texto-imagen {
            font-size: 8pt;
            margin-top: 3px;
            text-align: center;
        }
        
        .texto-centrado {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="page-container">
        
        {{-- HEADER CON LOGO E INFORMACIÓN --}}
        <div class="header">
            <div class="logo-container">
                <img src="{{ $logo_path }}" alt="Xante Logo" class="logo">
            </div>
            <div class="header-info">
                <div class="property-data">
                    <span class="property-label">Comunidad:</span>
                    <span class="property-value">{{ $comunidad ?? '' }}</span>
                </div>
                <div class="property-data">
                    <span class="property-label">Privada:</span>
                    <span class="property-value">{{ $domicilio_convenio ?? '' }}</span>
                </div>
                <div class="property-data">
                    <span class="property-label">Tipo:</span>
                    <span class="property-value">{{ $tipo_vivienda ?? '' }}</span>
                </div>
                <div class="property-data">
                    <span class="property-label">Precio de venta:</span>
                    <span class="property-value">${{ number_format($precio_promocion ?? 0, 2, '.', ',') }}</span>
                </div>
                <div class="date-box">
                    <span class="date-value">{{ $day ?? '__' }}</span> de 
                    <span class="date-value">{{ $month ?? '__________' }}</span> del 
                    <span class="date-value">{{ $year ?? '2025' }}</span>
                </div>
            </div>
        </div>

        {{-- TÍTULO PRINCIPAL --}}
        <div class="main-title">
            CONDICIONES PARA COMERCIALIZACIÓN XANTE
        </div>

        {{-- CONDICIÓN 1 --}}
        <div class="condition">
            <span class="condition-number">1.</span> Se solicita que la vivienda esté en condiciones óptimas (limpieza) para realizar visitas de
            prospectos.
        </div>

        {{-- CONDICIÓN 2 --}}
        <div class="condition">
            <span class="condition-number">2.</span> Confirma, en la siguiente casilla, entre A, B o C, la disponibilidad para visita de clientes de acuerdo
            con las siguientes opciones:
        </div>

        <div class="options-section">
            <div class="option-label">MEJOR OPCIÓN PARA MI: <span style="border-bottom: 1px solid #000; display: inline-block; min-width: 40px;"></span></div>
            <div style="margin-bottom: 8px; font-size: 9pt;">
                En caso de ser C. Requiero de anticipación <span class="hours-input"></span> horas
            </div>
            
            <div class="options-grid">
                <div class="option-box">
                    <div class="option-letter">A</div>
                    <div class="option-title">Acceso con días y horarios específicos</div>
                    <div class="option-description">*El titular dará acceso a la privada y vivienda</div>
                </div>
                
                <div class="option-box">
                    <div class="option-letter">B</div>
                    <div class="option-title">Entrega de juego de llaves para acceso peatonal y/o vehicular</div>
                    <div class="option-description">*Sólo disponible para viviendas deshabitadas</div>
                    <div class="option-note">
                        Al no contar con exclusividad, no nos hacemos responsables por daños. Al considerar que la vivienda
                        se pueda estar promoviendo, por igual, a terceros.
                    </div>
                </div>
                
                <div class="option-box">
                    <div class="option-letter">C</div>
                    <div class="option-title">Sujeto a disponibilidad.</div>
                    <div class="option-description">*Solicitud de acceso con 24 horas de anticipación.</div>
                    <div class="option-description" style="margin-top: 8px;">
                        O mencionar tiempo que se requiere de anticipación: <span class="hours-input"></span> horas
                    </div>
                </div>
            </div>
        </div>

        {{-- CONDICIONES 3-12 --}}
        <div class="condition">
            <span class="condition-number">3.</span> Notificar a su administración general y de privada la comercialización de su vivienda. Para dar a
            conocer las visitas que se estarán realizando de los prospectos.
        </div>

        <div class="condition">
            <span class="condition-number">4.</span> Es responsabilidad del titular realizar los arreglos que la vivienda requiera, como pintura, fugas,
            entre otros. Al momento en que sean solicitados, ya sea para avalúo y/o entrega de vivienda.
        </div>

        <div class="condition">
            <span class="condition-number">5.</span> Al recibir una intención de compra que NO sea por medio de Xante es obligación del cliente
            notificarlo al correo <span class="bold">contacto@xante.mx</span> en un máximo de 24 horas. De igual forma, Xante notificará
            al tener cliente confirmado para ya no promover la vivienda.
        </div>

        <div class="condition">
            <span class="condition-number">6.</span> Se requiere que se entregue la documentación con anticipación (check list) para armado de
            expediente. Y al momento de notificar la venta, solicitamos de su apoyo para que en un máximo de
            36 horas nos entregue la documentación completa actualizada y escaneada.
            
            <ol class="sub-list">
                <li>
                    Se solicitará expediente completo de vendedor(es) y vivienda para iniciar proceso de
                    compraventa. Le solicitamos contar con su documentación disponible y sus pagos de
                    servicios al corriente.
                </li>
                <li>
                    Al ser notificado de que su vivienda fue vendida, se comunicará con usted el área de
                    titulación para seguimiento e integración de su expediente.
                </li>
            </ol>
        </div>

        <div class="condition">
            <span class="condition-number">7.</span> Es responsabilidad del propietario tener al corriente todos sus servicios como agua, predial, luz,
            internet-telefonía y mantenimientos. Así como, la entrega de documentación en tiempo y forma
            para iniciar proceso de escrituración correspondiente a la compraventa.
        </div>

        <div class="condition">
            <span class="condition-number">8.</span> Debes considerar que existen gastos al momento de la venta de tu inmueble: como las
            certificaciones de no adeudo (depende del municipio), cancelación de hipoteca, en caso de que
            cuentes con un crédito(s) hipotecario e ISR, que corren por tu cuenta.
        </div>

        <div class="condition">
            <span class="condition-number">9.</span> Si al momento del apartado de la vivienda, el cliente reciba un anticipo, pagará a XANTE el 50% de
            la comisión pactada más el Impuesto al Valor Agregado, y; al momento de la formalización de la
            Escritura Pública pagará el 50% restante más el Impuesto al Valor Agregado. De no firmar contrato
            o promesa de compraventa y no existir un anticipo entre el comprador y el vendedor, la comisión de
            XANTE será pagada en su totalidad al momento de la escrituración cuando EL VENDEDOR reciba el
            pago total del inmueble, en un lapso no mayor a 12 horas.
        </div>

        <div class="condition">
            <span class="condition-number">10.</span> Al no contar con la exclusividad para la comercialización de la vivienda no nos hacemos
            responsables por pérdidas o desperfectos realizados en la vivienda.
        </div>

        <div class="condition">
            <span class="condition-number">11.</span> Al confirmar y enviar "Acuerdo de Promoción y Comercialización" deberá enviar fotos con las
            especificaciones presentadas (Anexo 1).
        </div>

        <div class="condition">
            <span class="condition-number">12.</span> Si su vivienda se encuentra con las siguientes características (sin muebles y se les solicita se
            encuentre limpia), puede agendar una cita con nosotros para realizar el recorrido virtual y sesión
            fotográfica de su casa-depa para nuestra página web y ficha técnica.
        </div>

        <div class="condition" style="margin-left: 20px;">
            Únicamente requerimos nos confirme enviando un correo a <span class="bold">ventas@xante.mx</span> para agendar su cita.
            De lo contrario, envía las fotografías de tu inmueble con las siguientes características.
        </div>

        {{-- ANEXO 1 --}}
        <div class="anexo-section">
            <div class="anexo-title">Anexo 1.</div>
            <ul class="anexo-list">
                <li>
                    Las fotos se requieren con buena iluminación y en buenas condiciones de limpieza.
                </li>
                <li>
                    Te solicitamos tomes fotografía de cada una de las plazas (sala, comedor, cocina, baño(s),
                    recámaras, alcoba, patio, jardín, cuarto de servicio, acceso al fraccionamiento y privada más
                    la fachada del inmueble) Así como del equipamiento con el que cuenta (cocina integral, cancel,
                    clósets, etc).
                </li>
                <li>
                    En caso de que se vaya a retirar el equipamiento que se tenga en la vivienda, mencionarlo.
                </li>
            </ul>
        </div>

        {{-- FIRMA --}}
        <div class="signature-section">
            <div class="signature-line"></div>
            <div class="signature-label">
                Nombre y firma de conformidad y enterado
            </div>
        </div>

        {{-- EJEMPLO DE FOTOGRAFÍAS --}}
        <div class="example-section" style="page-break-before: always; margin-top: 0;">
            Ejemplo de fotografías requeridas:
        </div>

        <div class="galeria-imagenes">
            {{-- FILA 1: 3 IMÁGENES --}}
            <div class="fila-imagenes">
                <div class="contenedor-imagen">
                    <img src="{{ $image_1_path }}" alt="Ejemplo fotografía 1" class="imagen-ejemplo">
                    <div class="texto-imagen">Fachada principal</div>
                </div>
                <div class="contenedor-imagen">
                    <img src="{{ $image_2_path }}" alt="Ejemplo fotografía 2" class="imagen-ejemplo">
                    <div class="texto-imagen">Sala de estar</div>
                </div>
                <div class="contenedor-imagen">
                    <img src="{{ $image_3_path }}" alt="Ejemplo fotografía 3" class="imagen-ejemplo">
                    <div class="texto-imagen">Comedor</div>
                </div>
            </div>
            
            {{-- FILA 2: 3 IMÁGENES --}}
            <div class="fila-imagenes">
                <div class="contenedor-imagen">
                    <img src="{{ $image_4_path }}" alt="Ejemplo fotografía 4" class="imagen-ejemplo">
                    <div class="texto-imagen">Cocina</div>
                </div>
                <div class="contenedor-imagen">
                    <img src="{{ $image_5_path }}" alt="Ejemplo fotografía 5" class="imagen-ejemplo">
                    <div class="texto-imagen">Recámara principal</div>
                </div>
                <div class="contenedor-imagen">
                    <img src="{{ $image_6_path }}" alt="Ejemplo fotografía 6" class="imagen-ejemplo">
                    <div class="texto-imagen">Baño</div>
                </div>
            </div>
            
            {{-- FILA 3: 2 IMÁGENES --}}
            <div class="fila-imagenes">
                <div class="contenedor-imagen">
                    <img src="{{ $image_7_path }}" alt="Ejemplo fotografía 7" class="imagen-ejemplo">
                    <div class="texto-imagen">Área exterior</div>
                </div>
                <div class="contenedor-imagen">
                    <img src="{{ $image_8_path }}" alt="Ejemplo fotografía 8" class="imagen-ejemplo">
                    <div class="texto-imagen">Vista general</div>
                </div>
            </div>
        </div>

        <div class="texto-centrado" style="margin-top: 30px;">
            <strong>Xante.mx</strong>
        </div>
    </div>
</body>
</html>