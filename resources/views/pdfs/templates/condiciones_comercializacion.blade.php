<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Condiciones para Comercialización XANTE</title>
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
        
        .titulo-principal {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0;
            line-height: 1.3;
        }
        
        .info-propiedad {
            margin: 20px 0;
            padding: 10px 0;
        }
        
        .campo-propiedad {
            display: inline-block;
            margin-right: 30px;
            font-weight: bold;
        }
        
        .campo-propiedad strong {
            text-decoration: underline;
        }
        
        .fecha {
            text-align: left;
            margin: 15px 0;
            font-size: 11pt;
        }
        
        .numero-condicion {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .texto-condicion {
            text-align: justify;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .tabla-opciones {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 10pt;
        }
        
        .tabla-opciones th {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            background-color: #f0f0f0;
            font-weight: bold;
            width: 33.33%;
        }
        
        .tabla-opciones td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        
        .campo-opcion {
            text-align: center;
            margin: 10px 0;
            font-weight: bold;
        }
        
        .campo-subrayado {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 50px;
            text-align: center;
            margin: 0 5px;
        }
        
        .firma {
            margin-top: 80px;
            text-align: center;
        }
        
        .linea-firma {
            border-top: 1px solid #000;
            width: 300px;
            margin: 60px auto 5px auto;
        }
        
        .texto-firma {
            font-weight: bold;
        }
        
        .galeria-imagenes {
            margin: 20px 0;
        }
        
        .fila-imagenes {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .contenedor-imagen {
            width: 48%;
            text-align: center;
        }
        
        .imagen-ejemplo {
            width: 100%;
            max-width: 300px;
            height: auto;
            border: 1px solid #ddd;
        }
        
        .texto-imagen {
            font-size: 9pt;
            margin-top: 5px;
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
        
        .container {
            position: relative;
        }
        
        .lista-viñetas {
            margin: 10px 0 10px 20px;
        }
        
        .lista-viñetas li {
            margin-bottom: 8px;
            text-align: justify;
        }
        
        .destacado {
            font-weight: bold;
        }
        
        .texto-centrado {
            text-align: center;
        }
        
        .texto-derecha {
            text-align: right;
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
            <div class="titulo-principal">CONDICIONES PARA COMERCIALIZACIÓN XANTE</div>
        </div>

        <div class="info-propiedad">
            <span class="campo-propiedad">
                <strong>Comunidad:</strong> {{ $comunidad ?? '________________' }}
            </span>
            <span class="campo-propiedad">
                <strong>Tipo:</strong> {{ $tipo_vivienda ?? '________________' }}
            </span>
            <br>
            <span class="campo-propiedad">
                <strong>Privada:</strong> {{ $privada ?? '________________' }}
            </span>
            <span class="campo-propiedad">
                <strong>Precio de venta:</strong> ${{ isset($precio_promocion) ? number_format($precio_promocion, 2) : '________' }}
            </span>
        </div>

        <div class="fecha">
            <span class="campo-subrayado">{{ $day ?? now()->format('d') }}</span> de 
            <span class="campo-subrayado">{{ $month ?? $monthNames[now()->format('n')] ?? '__________' }}</span> del 
            <span class="campo-subrayado">{{ $year ?? now()->format('Y') }}</span>
        </div>

        <div class="numero-condicion">1.</div>
        <div class="texto-condicion">
            Se solicita que la vivienda esté en condiciones óptimas (limpieza) para realizar visitas de prospectos.
        </div>

        <div class="numero-condicion">2.</div>
        <div class="texto-condicion">
            Confirma, en la siguiente casilla, entre A, B o C, la disponibilidad para visita de clientes de acuerdo con las siguientes opciones:
        </div>

        <div class="campo-opcion">
            MEJOR OPCIÓN PARA MI: <span class="campo-subrayado" style="min-width: 30px;">&nbsp;&nbsp;&nbsp;&nbsp;</span>
        </div>

        <div class="campo-opcion">
            En caso de ser C. Requiero de anticipación <span class="campo-subrayado" style="min-width: 30px;">&nbsp;&nbsp;&nbsp;&nbsp;</span> horas
        </div>

        <table class="tabla-opciones">
            <tr>
                <th>A</th>
                <th>B</th>
                <th>C</th>
            </tr>
            <tr>
                <td>
                    <strong>Acceso con días y horarios específicos</strong><br>
                    *El titular dará acceso a la privada y vivienda
                </td>
                <td>
                    <strong>Entrega de juego de llaves para acceso peatonal y/o vehicular</strong><br>
                    *Sólo disponible para viviendas deshabitadas<br>
                    *Al no contar con exclusividad, no nos hacemos responsables por daños.<br>
                    *Al considerar que la vivienda se pueda estar promoviendo, por igual, a terceros.
                </td>
                <td>
                    <strong>Sujeto a disponibilidad.</strong><br>
                    *Solicitud de acceso con 24 horas de anticipación.<br>
                    *O mencionar tiempo que se requiere de anticipación: _____ horas
                </td>
            </tr>
        </table>

        <div class="numero-condicion">3.</div>
        <div class="texto-condicion">
            Notificar a su administración general y de privada la comercialización de su vivienda. Para dar a conocer las visitas que se estarán realizando de los prospectos.
        </div>

        <div class="numero-condicion">4.</div>
        <div class="texto-condicion">
            Es responsabilidad del titular realizar los arreglos que la vivienda requiera, como pintura, fugas, entre otros. Al momento en que sean solicitados, ya sea para avalúo y/o entrega de vivienda.
        </div>

        <div class="numero-condicion">5.</div>
        <div class="texto-condicion">
            Al recibir una intención de compra que NO sea por medio de Xante es obligación del cliente notificarlo al correo contacto@xante.mx en un máximo de 24 horas. De igual forma, Xante notificará al tener cliente confirmado para ya no promover la vivienda.
        </div>

        <div class="numero-condicion">6.</div>
        <div class="texto-condicion">
            Se requiere que se entregue la documentación con anticipación (check list) para armado de expediente. Y al momento de notificar la venta, solicitamos de su apoyo para que en un máximo de 36 horas nos entregue la documentación completa actualizada y escaneada.
        </div>
    </div>

    {{-- PÁGINA 2 --}}
    <div class="salto-pagina"></div>
    <div class="container">
        <div class="numero-pagina">2</div>
        
        <div class="header">
            <div class="logo-container">
                <img src="{{ $logo_path }}" alt="Xante Logo" class="logo">
            </div>
            <div class="titulo-principal">CONDICIONES PARA COMERCIALIZACIÓN XANTE</div>
        </div>

        <div class="texto-condicion" style="margin-top: 20px;">
            a. Se solicitará expediente completo de vendedor(es) y vivienda para iniciar proceso de compraventa. Le solicitamos contar con su documentación disponible y sus pagos de servicios al corriente.
        </div>

        <div class="texto-condicion">
            b. Al ser notificado de que su vivienda fue vendida, se comunicará con usted el área de titulación para seguimiento e integración de su expediente.
        </div>

        <div class="numero-condicion">7.</div>
        <div class="texto-condicion">
            Es responsabilidad del propietario tener al corriente todos sus servicios como agua, predial, luz, internet-telefonía y mantenimientos. Así como, la entrega de documentación en tiempo y forma para iniciar proceso de escrituración correspondiente a la compraventa.
        </div>

        <div class="numero-condicion">8.</div>
        <div class="texto-condicion">
            Debes considerar que existen gastos al momento de la venta de tu inmueble: como las certificaciones de no adeudo (depende del municipio), cancelación de hipoteca, en caso de que cuentes con un crédito(s) hipotecario e ISR, que corren por tu cuenta.
        </div>

        <div class="numero-condicion">9.</div>
        <div class="texto-condicion">
            Si al momento del apartado de la vivienda, el cliente reciba un anticipo, pagará a XANTE el 50% de la comisión pactada más el Impuesto al Valor Agregado, y; al momento de la formalización de la Escritura Pública pagará el 50% restante más el Impuesto al Valor Agregado. De no firmar contrato o promesa de compraventa y no existir un anticipo entre el comprador y el vendedor, la comisión de XANTE será pagada en su totalidad al momento de la escrituración cuando EL VENDEDOR reciba el pago total del inmueble, en un lapso no mayor a 12 horas.
        </div>

        <div class="numero-condicion">10.</div>
        <div class="texto-condicion">
            Al no contar con la exclusividad para la comercialización de la vivienda no nos hacemos responsables por pérdidas o desperfectos realizados en la vivienda.
        </div>

        <div class="numero-condicion">11.</div>
        <div class="texto-condicion">
            Al confirmar y enviar "Acuerdo de Promoción y Comercialización" deberá enviar fotos con las especificaciones presentadas (Anexo 1).
        </div>

        <div class="numero-condicion">12.</div>
        <div class="texto-condicion">
            Si su vivienda se encuentra con las siguientes características (sin muebles y se les solicita se encuentre limpia), puede agendar una cita con nosotros para realizar el recorrido virtual y sesión fotográfica de su casa-depa para nuestra página web y ficha técnica. Únicamente requerimos nos confirme enviando un correo a ventas@xante.mx para agendar su cita. De lo contrario, envía las fotografías de tu inmueble con las siguientes características.
        </div>

        <div class="texto-condicion" style="margin-top: 25px;">
            <strong>Anexo 1.</strong>
        </div>

        <ul class="lista-viñetas">
            <li>Las fotos se requieren con buena iluminación y en buenas condiciones de limpieza.</li>
            <li>Te solicitamos tomes fotografía de cada una de las plazas (sala, comedor, cocina, baño(s), recámaras, alcoba, patio, jardín, cuarto de servicio, acceso al fraccionamiento y privada más la fachada del inmueble) Así como del equipamiento con el que cuenta (cocina integral, cancel, clósets, etc).</li>
            <li>En caso de que se vaya a retirar el equipamiento que se tenga en la vivienda, mencionarlo.</li>
        </ul>

        <div class="firma">
            <div class="linea-firma"></div>
            <div class="texto-firma">Nombre y firma de conformidad y enterado</div>
        </div>
    </div>

    {{-- PÁGINA 3 --}}
    <div class="salto-pagina"></div>
    <div class="container">
        <div class="numero-pagina">3</div>
        
        <div class="header">
            <div class="logo-container">
                <img src="{{ $logo_path }}" alt="Xante Logo" class="logo">
            </div>
            <div class="titulo-principal">CONDICIONES PARA COMERCIALIZACIÓN XANTE</div>
        </div>

        <div class="texto-condicion texto-centrado" style="margin: 20px 0;">
            <strong>Ejemplo de fotografías requeridas:</strong>
        </div>

        <div class="galeria-imagenes">
            <div class="fila-imagenes">
                <div class="contenedor-imagen">
                    <img src="{{ $image_1_path }}" alt="Ejemplo fotografía 1" class="imagen-ejemplo">
                    <div class="texto-imagen">Fachada principal</div>
                </div>
                <div class="contenedor-imagen">
                    <img src="{{ $image_2_path }}" alt="Ejemplo fotografía 2" class="imagen-ejemplo">
                    <div class="texto-imagen">Sala de estar</div>
                </div>
            </div>
            
            <div class="fila-imagenes">
                <div class="contenedor-imagen">
                    <img src="{{ $image_3_path }}" alt="Ejemplo fotografía 3" class="imagen-ejemplo">
                    <div class="texto-imagen">Comedor</div>
                </div>
                <div class="contenedor-imagen">
                    <img src="{{ $image_4_path }}" alt="Ejemplo fotografía 4" class="imagen-ejemplo">
                    <div class="texto-imagen">Cocina</div>
                </div>
            </div>
            
            <div class="fila-imagenes">
                <div class="contenedor-imagen">
                    <img src="{{ $image_5_path }}" alt="Ejemplo fotografía 5" class="imagen-ejemplo">
                    <div class="texto-imagen">Recámara principal</div>
                </div>
                <div class="contenedor-imagen">
                    <img src="{{ $image_6_path }}" alt="Ejemplo fotografía 6" class="imagen-ejemplo">
                    <div class="texto-imagen">Baño</div>
                </div>
            </div>
            
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