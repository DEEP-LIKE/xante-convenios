{{-- resources/views/pdfs/templates/checklist_expediente.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist Apertura Convenio de Promoción Inmobiliaria</title>
    <style>
        @page {
            size: letter;
            margin: 2cm 2.5cm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
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
            margin-bottom: 25px;
            font-size: 10pt;
        }
        
        .header .fecha {
            text-decoration: underline;
        }
        
        /* LOGO */
        .logo-section {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo {
            width: 120px;
            height: auto;
            margin-bottom: 10px;
        }
        
        /* TÍTULO */
        .main-title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 25px;
            line-height: 1.3;
        }
        
        /* SALUDO */
        .greeting {
            text-align: justify;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .greeting-name {
            text-decoration: underline;
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
            margin: 25px 0;
            padding: 8px 12px;
            border: 2px solid #000;
            background-color: #f5f5f5;
        }
        
        .client-box .label {
            font-weight: bold;
            display: inline-block;
            min-width: 80px;
        }
        
        .client-box .value {
            text-decoration: underline;
        }
        
        .client-location {
            margin-top: 5px;
        }
        
        /* SECCIONES DE DOCUMENTACIÓN */
        .doc-section {
            margin: 25px 0;
        }
        
        .doc-section-title {
            background-color: #A8D08D;
            font-weight: bold;
            font-size: 11pt;
            padding: 6px 10px;
            margin-bottom: 12px;
            text-transform: uppercase;
            border: 1px solid #000;
        }
        
        /* TABLA DE DOCUMENTOS */
        .doc-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .doc-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: middle;
        }
        
        .doc-number {
            width: 30px;
            text-align: center;
            font-weight: bold;
            background-color: #f5f5f5;
        }
        
        .doc-name {
            font-weight: normal;
        }
        
        .doc-checkbox {
            width: 30px;
            text-align: center;
        }
        
        .checkbox-square {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #000;
            vertical-align: middle;
        }
        
        /* NOTAS */
        .notes-section {
            margin: 25px 0;
        }
        
        .note-item {
            margin-bottom: 12px;
            padding-left: 20px;
            position: relative;
            text-align: justify;
            line-height: 1.6;
        }
        
        .note-item::before {
            content: "•";
            position: absolute;
            left: 5px;
            font-weight: bold;
            font-size: 14pt;
        }
        
        .note-emphasis {
            font-style: italic;
            margin-top: 15px;
            text-align: center;
            font-weight: bold;
        }
        
        /* UTILIDADES */
        .text-center {
            text-align: center;
        }
        
        .font-bold {
            font-weight: bold;
        }
        
        .underline {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="page-container">
        
        {{-- FECHA EN HEADER --}}
        <div class="header">
            <span class="fecha">{{ $day ?? '__' }} de {{ $month ?? '__________' }} de {{ $year ?? '2025' }}</span>
        </div>

        {{-- LOGO --}}
        <div class="logo-section">
            <img src="{{ $logo_path }}" alt="Xante Logo" class="logo">
        </div>

        {{-- TÍTULO PRINCIPAL --}}
        <div class="main-title">
            CHECK LIST APERTURA<br>
            CONVENIO DE PROMOCIÓN INMOBILIARIA
        </div>

        {{-- SALUDO --}}
        <div class="greeting">
            Hola <span class="greeting-name">{{ $holder_name ?? '____________________' }}</span>, muchas gracias por la aceptación de
            nuestro convenio de comercialización, estaremos promocionando su vivienda a través de
            nuestra página web, redes sociales y portales inmobiliarios.
        </div>

        {{-- PÁRRAFO EXPLICATIVO --}}
        <div class="paragraph">
            Para seguir ofreciéndole un excelente servicio, le solicitamos nos haga llegar la siguiente
            documentación, que formará parte de su expediente. Y al momento de la venta, eficientar
            los tiempos para el cierre de la operación.
        </div>

        <div class="paragraph">
            Es importante que cuente con su escritura original, ya que, es un documento necesario para
            la formalización y cierre de la operación, que le solicitará la Notaría.
        </div>

        {{-- INFORMACIÓN DEL CLIENTE --}}
        <div class="client-box">
            <div>
                <span class="label">CLIENTE:</span>
                <span class="value">{{ strtoupper($holder_name ?? '____________________') }}</span>
            </div>
            <div class="client-location">
                <span class="value">{{ strtoupper($comunidad ?? 'REAL ________') }}</span>, 
                <span class="value">{{ strtoupper($property_community ?? 'PRIVADA ________') }}</span>.
            </div>
        </div>

        {{-- DOCUMENTACIÓN TITULAR --}}
        <div class="doc-section">
            <div class="doc-section-title">DOCUMENTACIÓN TITULAR</div>
            
            <table class="doc-table">
                <tr>
                    <td class="doc-number">1</td>
                    <td class="doc-name">INE (A color, tamaño original, no fotos)</td>
                    <td class="doc-checkbox"><span class="checkbox-square"></span></td>
                </tr>
                <tr>
                    <td class="doc-number">2</td>
                    <td class="doc-name">CURP (Mes corriente)</td>
                    <td class="doc-checkbox"><span class="checkbox-square"></span></td>
                </tr>
                <tr>
                    <td class="doc-number">3</td>
                    <td class="doc-name">Constancia de Situación Fiscal (Mes corriente, completa)</td>
                    <td class="doc-checkbox"><span class="checkbox-square"></span></td>
                </tr>
                <tr>
                    <td class="doc-number">4</td>
                    <td class="doc-name">Comprobante de Domicilio Vivienda (Mes corriente)</td>
                    <td class="doc-checkbox"><span class="checkbox-square"></span></td>
                </tr>
                <tr>
                    <td class="doc-number">5</td>
                    <td class="doc-name">Comprobante de Domicilio Titular (Mes corriente)</td>
                    <td class="doc-checkbox"><span class="checkbox-square"></span></td>
                </tr>
                <tr>
                    <td class="doc-number">6</td>
                    <td class="doc-name">Acta Nacimiento</td>
                    <td class="doc-checkbox"><span class="checkbox-square"></span></td>
                </tr>
                <tr>
                    <td class="doc-number">7</td>
                    <td class="doc-name">Acta Matrimonio (Si aplica)</td>
                    <td class="doc-checkbox"><span class="checkbox-square"></span></td>
                </tr>
                <tr>
                    <td class="doc-number">8</td>
                    <td class="doc-name">Caratula Estado de Cuenta Bancario con Datos Fiscales (Mes corriente)</td>
                    <td class="doc-checkbox"><span class="checkbox-square"></span></td>
                </tr>
            </table>
        </div>

        {{-- DOCUMENTACIÓN PROPIEDAD --}}
        <div class="doc-section">
            <div class="doc-section-title">DOCUMENTACIÓN PROPIEDAD</div>
            
            <table class="doc-table">
                <tr>
                    <td class="doc-number">1</td>
                    <td class="doc-name">
                        Instrumento Notarial con Antecedentes Registrales (Datos Registrales y<br>
                        Traslado de Dominio) Escaneada, visible
                    </td>
                    <td class="doc-checkbox"><span class="checkbox-square"></span></td>
                </tr>
                <tr>
                    <td class="doc-number">2</td>
                    <td class="doc-name">Recibo predial (Mes corriente)</td>
                    <td class="doc-checkbox"><span class="checkbox-square"></span></td>
                </tr>
                <tr>
                    <td class="doc-number">3</td>
                    <td class="doc-name">Recibo de Agua (Mes corriente)</td>
                    <td class="doc-checkbox"><span class="checkbox-square"></span></td>
                </tr>
                <tr>
                    <td class="doc-number">4</td>
                    <td class="doc-name">Recibo CFE con datos fiscales (Mes corriente)</td>
                    <td class="doc-checkbox"><span class="checkbox-square"></span></td>
                </tr>
            </table>
        </div>

        {{-- NOTAS IMPORTANTES --}}
        <div class="notes-section">
            <div class="note-item">
                <strong>Nota:</strong> Esta documentación de apertura, es necesaria para realizar los formatos de
                venta, y a su vez, iniciar el proceso de avalúo, una vez confirmado el apartado.
            </div>
            
            <div class="note-item">
                La documentación le solicitamos sea actualizada cada mes o bimestre, dependiendo
                sea el caso del pago de sus servicios.
            </div>
        </div>

        {{-- NOTA FINAL EN CURSIVA --}}
        <div class="note-emphasis">
            *La documentación debe ser escaneada, no fotos.
        </div>

    </div>
</body>
</html>