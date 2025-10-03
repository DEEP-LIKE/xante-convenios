{{-- resources/views/pdfs/templates/acuerdo_promocion.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acuerdo de Promoción y Comercialización del Inmueble</title>
    <style>
        @page {
            margin: 2cm 2.5cm;
            @bottom-center {
                content: "www.xante.mx " counter(page);
                font-size: 9pt;
                color: #666;
                font-family: Arial, Helvetica, sans-serif;
            }
        }
        
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #000;
            margin: 0;
            padding: 0;
        }
        
        .header {
            text-align: center;
            margin-bottom: 25px;
            position: relative;
        }
        
        .header .logo-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 120px;
        }
        
        .header .logo {
            width: 100px;
            height: auto;
        }
        
        .header h1 {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.3;
            padding-top: 10px;
        }
        
        .fecha-ubicacion {
            text-align: left;
            margin: 20px 0;
            font-size: 11pt;
        }
        
        .subtitulo {
            font-weight: bold;
            text-transform: uppercase;
            margin: 20px 0 15px 0;
            font-size: 11pt;
            text-align: center;
        }
        
        .parrafo {
            text-align: justify;
            margin-bottom: 15px;
            line-height: 1.6;
            text-indent: 20px;
        }
        
        .destacado {
            font-weight: bold;
        }
        
        .precio-box {
            text-align: center;
            margin: 25px 0;
            font-size: 12pt;
            padding: 10px;
            border: 1px solid #333;
            background-color: #f9f9f9;
        }
        
        .datos-bancarios {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #333;
            background-color: #f9f9f9;
        }
        
        .datos-bancarios .titulo {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .lista-obligaciones {
            margin: 15px 0 15px 20px;
        }
        
        .lista-obligaciones li {
            margin-bottom: 10px;
            text-align: justify;
            line-height: 1.6;
        }
        
        .lista-obligaciones ul {
            margin: 5px 0 5px 20px;
        }
        
        .lista-obligaciones ul li {
            margin-bottom: 5px;
        }
        
        .firmas {
            margin-top: 60px;
            page-break-inside: avoid;
        }
        
        .firma-seccion {
            margin-top: 40px;
        }
        
        .firma-linea {
            border-top: 1px solid #000;
            width: 100%;
            margin: 50px auto 5px auto;
        }
        
        .firma-nombre {
            font-weight: bold;
            margin-top: 5px;
            text-align: center;
        }
        
        .aviso-privacidad {
            margin-top: 30px;
            font-size: 9pt;
            text-align: center;
            color: #666;
        }
        
        .salto-pagina {
            page-break-before: always;
        }
        
        .numero-pagina {
            position: absolute;
            top: -25px;
            right: 0;
            font-size: 9pt;
            color: #666;
        }
        
        strong {
            font-weight: bold;
        }
        
        .underline {
            text-decoration: underline;
        }
        
        .container {
            position: relative;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        /* Estilo para tablas de firmas */
        .tabla-firmas {
            width: 100%;
            margin-top: 80px;
            border-collapse: collapse;
        }
        
        .tabla-firmas td {
            width: 50%;
            vertical-align: top;
            padding: 0 10px;
        }
        
        .firma-contenedor {
            text-align: center;
        }
        
        .linea-firma {
            border-top: 1px solid #000;
            width: 100%;
            margin-top: 60px;
        }
        
        .nombre-firma {
            font-weight: bold;
            margin-top: 5px;
        }
        
        .titulo-firma {
            font-weight: bold;
            margin-bottom: 10px;
            text-align: left;
        }
    </style>
</head>
<body>
    @php
        $monthNames = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
        ];
    @endphp

    {{-- PÁGINA 1 --}}
    <div class="container">
        <div class="numero-pagina">1</div>
        
        <div class="header">
            <div class="logo-container">
                <img src="{{ $logo_path }}" alt="Xante Logo" class="logo">
            </div>
            <h1>
                ACUERDO DE PROMOCIÓN Y COMERCIALIZACIÓN DEL<br>
                INMUEBLE
            </h1>
        </div>

        <div class="fecha-ubicacion">
            Estado de <strong class="underline">{{ $property_state ?? $wizardData['estado'] ?? '___________' }}</strong> a 
            <strong class="underline">{{ $day ?? now()->format('d') }}</strong> de 
            <strong class="underline">{{ $month ?? $monthNames[now()->format('n')] ?? '___________' }}</strong> de 
            <strong class="underline">{{ $year ?? now()->format('Y') }}</strong>
        </div>

        <div class="subtitulo">
            ACUERDO DE PROMOCIÓN INMOBILIARIA ENTRE<br>
            <span class="underline">{{ strtoupper($wizardData['holder_name'] ?? '___________________________') }}</span> COMO EL VENDEDOR Y XANTE & VI, S.A.P.I. DE C.V
        </div>

        <div class="parrafo">
            Por medio de esta carta el vendedor(es) <strong class="underline">{{ strtoupper($wizardData['holder_name'] ?? '___________________________') }}</strong> 
            autoriza a XANTE, de manera no exclusiva, a realizar la promoción y publicidad que considere necesaria para 
            lograr la venta del inmueble (en adelante el "Inmueble") CON EL NÚMERO INTERIOR 
            "<strong class="underline">{{ $wizardData['numero_interior'] ?? '____' }}</strong>", PERTENECIENTE AL RÉGIMEN DE PROPIEDAD EN 
            CONDOMINIO DENOMINADO "<strong class="underline">{{ strtoupper($wizardData['comunidad'] ?? 'PRIVADA ___________') }}</strong>",
        </div>

        <div class="parrafo">
            CONSTITUIDO SOBRE EL LOTE "<strong class="underline">{{ $wizardData['lote'] ?? '____' }}</strong>", 
            MANZANA "<strong class="underline">{{ $wizardData['manzana'] ?? 'M' }}</strong>" ETAPA 
            <strong class="underline">{{ $wizardData['etapa'] ?? '____' }}</strong>, DE LA COMUNIDAD DENOMINADA 
            "<strong class="underline">{{ strtoupper($wizardData['comunidad'] ?? '___________') }}</strong>", EN EL MUNICIPIO DE 
            <strong class="underline">{{ strtoupper($wizardData['municipio'] ?? '___________') }}</strong>, ESTADO DE 
            <strong class="underline">{{ strtoupper($wizardData['estado'] ?? '___________') }}</strong>.
        </div>

        <div class="precio-box">
            El precio de promoción del inmueble será de: <strong>${{ number_format(floatval(str_replace(',', '', $wizardData['precio_promocion'] ?? 0)), 2, '.', ',') }}</strong><br>
            (<strong>{{ $wizardData['precio_promocion_letras'] ?? '___________________________' }} PESOS 00/100 M.N.</strong>)
        </div>

        <div class="parrafo">
            XANTE registrará a los prospectos interesados en el Inmueble por medio de correo electrónico y/o documento 
            firmado físico o digitalmente.
        </div>

        <div class="parrafo">
            Se reconocerá como Prospecto XANTE al propio prospecto, su esposo o esposa, su concubino o concubina, 
            sus hijos, su familia ascendiente y descendiente y/o el representante legal del mismo.
        </div>

        <div class="parrafo">
            El <strong>VENDEDOR(ES)</strong> expresamente reconoce que la comisión que cubrirá a <strong>XANTE</strong> por la promoción inmobiliaria 
            será la cantidad equivalente al <strong>{{ $wizardData['porcentaje_comision'] ?? '6.5' }}% 
            {{ $wizardData['porcentaje_comision_letras'] ?? 'seis punto cinco por ciento' }}</strong> más el impuesto al valor 
            agregado (IVA) sobre el precio por la venta del Inmueble. El cálculo del IVA es sobre el monto de la comisión.
        </div>

        <div class="parrafo">
            El pago por concepto de comisión a XANTE sólo se pagará por EL VENDEDOR(ES) en caso de cerrarse la 
            operación con un prospecto XANTE. El pago de dicha comisión se realizará de la siguiente forma:
        </div>

        <div class="parrafo">
            Al momento de la formalización de la escritura pública se pagará el 100% de la comisión lo que equivale al 
            <strong>{{ $wizardData['porcentaje_comision'] ?? '6.5' }}% ({{ ucfirst($wizardData['porcentaje_comision_letras'] ?? 'Seis punto cinco por ciento') }})</strong> 
            más el Impuesto al Valor Agregado.
        </div>

        <div class="parrafo">
            De no firmar contrato o promesa de compraventa y no existir un anticipo entre el comprador y el vendedor, 
            la comisión de XANTE será pagada en su totalidad al momento de la escrituración cuando EL VENDEDOR(ES) 
            reciban el pago total del Inmueble.
        </div>

        <div class="parrafo">
            El VENDEDOR(ES) se compromete a hacer los pagos correspondientes dentro de las 24 (veinticuatro) horas 
            siguientes a la fecha de condición de pago, mediante depósito o transferencia bancaria a la cuenta:
        </div>
    </div>

    {{-- PÁGINA 2 --}}
    <div class="page-break"></div>
    <div class="container">
        <div class="numero-pagina">2</div>
        
        <div class="header">
            <div class="logo-container">
                <img src="{{ $logo_path }}" alt="Xante Logo" class="logo">
            </div>
            <h1>
                ACUERDO DE PROMOCIÓN Y COMERCIALIZACIÓN DEL<br>
                INMUEBLE
            </h1>
        </div>

        <div class="datos-bancarios">
            <div class="titulo">Nombre titular: <strong>XANTE & VI, S.A.P.I. DE C.V.</strong></div>
            <div class="titulo">Banco: <strong>{{ $wizardData['bank_name'] ?? 'BBVA' }}</strong></div>
            <div class="titulo">Cuenta: <strong>{{ $wizardData['bank_account'] ?? '___________________' }}</strong></div>
            <div class="titulo">CLABE: <strong>{{ $wizardData['bank_clabe'] ?? '___________________' }}</strong></div>
        </div>

        <div class="parrafo" style="margin-top: 25px;">
            <strong>El VENDEDOR(ES) acepta y reconoce lo siguiente:</strong>
        </div>

        <ul class="lista-obligaciones">
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
    <div class="container">
        <div class="numero-pagina">3</div>
        
        <div class="header">
            <div class="logo-container">
                <img src="{{ $logo_path }}" alt="Xante Logo" class="logo">
            </div>
            <h1>
                ACUERDO DE PROMOCIÓN Y COMERCIALIZACIÓN DEL<br>
                INMUEBLE
            </h1>
        </div>

        <ul class="lista-obligaciones" style="margin-top: 20px;">
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

        <div class="parrafo" style="margin-top: 25px;">
            XANTE dará tratamiento a este dato personal en términos del Aviso de Privacidad que se encuentra en el 
            portal web de XANTE.
        </div>

        <div class="parrafo" style="margin-top: 20px;">
            En cumplimiento a lo establecido en las leyes, se hace constar que la información establecida en este 
            acuerdo, así como la documentación entregada entre ambas partes es confidencial y/o privilegiada y por 
            lo tanto su uso está destinado exclusivamente para los fines de este y para la promoción de los 
            inmuebles.
        </div>

        {{-- SECCIÓN DE FIRMAS --}}
        <table class="tabla-firmas">
            <tr>
                <td>
                    <div class="firma-contenedor">
                        <div class="titulo-firma">EL VENDEDOR(ES)</div>
                        <div class="linea-firma"></div>
                        <div class="nombre-firma">{{ strtoupper($wizardData['holder_name'] ?? 'NOMBRE Y FIRMA VENDEDOR') }}</div>
                    </div>
                </td>
                <td>
                    <div class="firma-contenedor">
                        <div class="titulo-firma">XANTE & VI, S.A.P.I. DE C.V.</div>
                        <div class="linea-firma"></div>
                        <div class="nombre-firma">C.P. CÉSAR RODRÍGUEZ REYES</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="aviso-privacidad">
            Las partes se comprometen a guardar absoluta discreción de la información confidencial. Estando a la vista<br>
            para el vendedor el aviso de privacidad de XANTE en:<br>
            <a href="https://xante.mx/assets/pdf/AVISO%20DE%20PRIVACIDAD%20PROMOTORA.pdf" style="color: #0066cc;">
                https://xante.mx/assets/pdf/AVISO%20DE%20PRIVACIDAD%20PROMOTORA.pdf
            </a>
        </div>
    </div>
</body>
</html>