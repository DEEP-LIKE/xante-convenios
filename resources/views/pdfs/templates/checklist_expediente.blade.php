<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Checklist Apertura Convenio</title>
    <style>
        @page { margin: 40px 50px; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #333;
        }
        header {
            text-align: center;
            margin-bottom: 20px;
        }
        header img {
            max-height: 80px;
        }
        h1 {
            font-size: 16px;
            text-align: center;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .section-title {
            background-color: #f2f2f2;
            font-weight: bold;
            padding: 5px;
            margin-top: 15px;
            border: 1px solid #ccc;
        }
        .content {
            margin-bottom: 15px;
            line-height: 1.5;
            text-align: justify;
        }
        .field {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 120px;
            padding: 0 5px;
            font-weight: bold;
        }
        ul {
            list-style: none;
            padding-left: 0;
        }
        ul li {
            margin: 4px 0;
        }
        .checkbox {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 1px solid #000;
            margin-right: 8px;
        }
        .footer-note {
            font-size: 11px;
            margin-top: 20px;
            text-align: justify;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        table td {
            padding: 5px;
            vertical-align: top;
        }
    </style>
</head>
<body>
<header>
    <img src="{{ $logo_path }}" alt="Logo Xante">
</header>

<h1>CHECK LIST APERTURA <br> CONVENIO DE PROMOCIÓN INMOBILIARIA</h1>

<div class="content">
    Hola <span class="field">{{ $holder_name ?? '________' }}</span>, muchas gracias por la aceptación de nuestro convenio 
    de comercialización, estaremos promocionando su vivienda a través de nuestra página web, redes sociales y portales inmobiliarios.
</div>

<div class="content">
    Para seguir ofreciéndole un excelente servicio, le solicitamos nos haga llegar la siguiente documentación, que formará parte 
    de su expediente. Y al momento de la venta, eficientar los tiempos para el cierre de la operación.
</div>

<div class="content">
    Es importante que cuente con su escritura original, ya que es un documento necesario para la formalización y cierre de la operación, 
    que le solicitará la Notaría.
</div>

<div class="section-title">CLIENTE</div>
<p>Nombre: <span class="field">{{ $holder_name ?? '________' }}</span></p>
<p>Privada: <span class="field">{{ $domicilio_convenio ?? '________' }}</span></p>
<p>Comunidad: <span class="field">{{ $comunidad ?? '________' }}</span></p>

<div class="section-title">DOCUMENTACIÓN TITULAR</div>
<ul>
    <li><span class="checkbox"></span> INE (A color, tamaño original, no fotos)</li>
    <li><span class="checkbox"></span> CURP (Mes corriente)</li>
    <li><span class="checkbox"></span> Constancia de Situación Fiscal (Mes corriente, completa)</li>
    <li><span class="checkbox"></span> Comprobante de Domicilio Vivienda (Mes corriente)</li>
    <li><span class="checkbox"></span> Comprobante de Domicilio Titular (Mes corriente)</li>
    <li><span class="checkbox"></span> Acta de Nacimiento</li>
    <li><span class="checkbox"></span> Acta de Matrimonio (Si aplica)</li>
    <li><span class="checkbox"></span> Carátula Estado de Cuenta Bancario con Datos Fiscales (Mes corriente)</li>
</ul>

<div class="section-title">DOCUMENTACIÓN PROPIEDAD</div>
<ul>
    <li><span class="checkbox"></span> Instrumento Notarial con Antecedentes Registrales</li>
    <li><span class="checkbox"></span> Recibo predial (Mes corriente)</li>
    <li><span class="checkbox"></span> Recibo de Agua (Mes corriente)</li>
    <li><span class="checkbox"></span> Recibo CFE con datos fiscales (Mes corriente)</li>
</ul>

<div class="footer-note">
    • Nota: Esta documentación de apertura es necesaria para realizar los formatos de venta, y a su vez, iniciar el proceso de avalúo, 
    una vez confirmado el apartado.<br>
    • La documentación le solicitamos sea actualizada cada mes o bimestre, dependiendo el caso del pago de sus servicios.<br>
    * La documentación debe ser escaneada, no fotos.
</div>

</body>
</html>
