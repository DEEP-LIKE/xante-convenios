<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos de su Convenio Inmobiliario</title>
    <style>
       body {
            font-family: 'Franie', Arial, sans-serif; /* Usando Franie como base, aunque las fuentes no se cargarán en todos los clientes de correo */
            line-height: 1.6;
            color: #342970; /* Azul Oscuro Xante para texto principal */
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4; /* Fondo ligero */
        }
        .header {
            background: #6C2582; /* Morado Principal a Morado Medio */
            color: #ffffff !important;
            padding: 30px 20px;
            text-align: center;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .header h1 {
            color: #ffffff !important;
            font-weight: 800;
            margin: 10px 0;
        }
        .header p {
            color: #ffffff !important;
            font-weight: 600;
            margin: 0;
        }
        .logo {
            font-family: 'Bitcheese', sans-serif; /* Fuente display para títulos */
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }
        .content {
            background: #fff;
            padding: 30px 20px;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
        }
        .info-box {
            background: #f0e6f5; /* Fondo muy claro de Morado */
            border-left: 5px solid #D63B8E; /* Borde Rosa Xante */
            padding: 20px;
            margin: 25px 0;
            border-radius: 8px;
        }
        .info-box h3 {
            color: #6C2582 !important; /* Morado Principal */
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #dcdcdc;
        }
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .info-label {
            font-weight: bold;
            color: #342970; /* Azul Oscuro Xante */
        }
        .info-value {
            color: #6C2582; /* Morado Principal */
            font-weight: 600;
        }
        .documents-section {
            background: #ecf3e2; /* Fondo de Verde Lima muy claro */
            border: 1px solid #BDCE0F; /* Borde Verde Lima */
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .documents-section h3 {
            color: #6C2582; /* Morado Principal */
        }
        .document-list {
            list-style: none;
            padding: 0;
            margin: 15px 0 0 0;
        }
        .document-list li {
            background: white;
            margin: 8px 0;
            padding: 12px 15px;
            border-radius: 6px;
            border-left: 3px solid #BDCE0F; /* Verde Lima Xante */
            display: flex;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .document-icon {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            fill: #BDCE0F; /* Verde Lima Xante */
        }
        .next-steps {
            background: #fff8e6; /* Fondo de Amarillo muy claro */
            border: 1px solid #FFD729; /* Borde Amarillo Xante */
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .next-steps h3 {
            color: #AD167F; /* Magenta Xante */
            margin-top: 0;
        }
        .next-steps ol {
            color: #342970;
            margin: 15px 0;
            padding-left: 20px;
        }
        .next-steps li {
            margin-bottom: 8px;
        }
        .footer {
            background: #342970; /* Azul Oscuro Xante */
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 12px 12px;
            font-size: 14px;
        }
        .contact-info {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #5d538e; /* Línea clara de separación */
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: #D63B8E; /* Rosa Xante para botones principales */
            color: white !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            margin: 10px 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: background 0.3s ease;
            box-shadow: 0 4px 8px rgba(214, 59, 142, 0.4);
        }
        .btn-secondary {
            background: #6C2582 !important; /* Morado Principal para botón secundario */
            box-shadow: 0 4px 8px rgba(108, 37, 130, 0.4);
        }
        .btn:hover {
            opacity: 0.9;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .info-row {
                flex-direction: column;
            }
            .info-value {
                margin-top: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">XANTE.MX</div>
        <h1>Sus Documentos Están Listos</h1>
        <p>Convenio Inmobiliario - Documentación Oficial</p>
    </div>

    <div class="content">
        <h2>Estimado/a {{ $clientName }},</h2>
        
        <p>Recibimos su documentación inicial —escritura, predial, identificación oficial, constancia de situación fiscal y acta de matrimonio— y con ella ya preparamos su <strong>Acuerdo de Promoción Inmobiliaria</strong>. Lo encontrará adjunto a este correo, listo para su revisión y firma.</p>

        <div class="info-box">
            <h3 style="margin-top: 0; color: #6C2582;">📋 Información del Convenio</h3>
            <div class="info-row">
                <span class="info-label">Propiedad:</span>
                <span class="info-value">{{ $propertyAddress }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Valor del Convenio:</span>
                <span class="info-value">${{ $valorConvenio }} MXN</span>
            </div>
            <div class="info-row">
                <span class="info-label">Fecha de Generación:</span>
                <span class="info-value">{{ $fechaGeneracion ?? now()->format('d/m/Y') }}</span>
            </div>
        </div> 

        <div class="next-steps" style="background: #f0e6f5; border: 1px solid #d1b2df;">
            <h3 style="color: #6C2582;">🔄 Su proceso, en dos momentos</h3>
            <ul style="color: #342970; margin: 15px 0; padding-left: 20px; list-style-type: disc;">
                <li style="margin-bottom: 12px;">
                    <strong>Ahora (convenio):</strong> Con los documentos que ya nos compartió es suficiente para firmar el acuerdo y firmado iniciar la promoción de su vivienda. No necesitará enviar nada más por el momento.
                </li>
                <li>
                    <strong>Cuando confirmemos la venta:</strong> Le pediremos completar el resto de su expediente (checklist completo adjunto) y actualizar los documentos con vigencia mensual. Le avisaremos con tiempo y le acompañaremos en cada paso.
                </li>
            </ul>
        </div>

        <div class="documents-section">
            <h3 style="margin-top: 0; color: #6C2582;">📎 Documentos Adjuntos</h3>
            <p>Se encuentran adjuntos los siguientes documentos oficiales:</p>
            <ul class="document-list">
                <li>
                    <svg class="document-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm0 2h12v8H4V6z" clip-rule="evenodd"/>
                    </svg>
                    <strong>Acuerdo de Promoción Inmobiliaria</strong>&nbsp;(convenio para firma)
                </li>
                <li>
                    <svg class="document-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm0 2h12v8H4V6z" clip-rule="evenodd"/>
                    </svg>
                    <strong>Condiciones para Comercialización</strong>
                </li>
                <li>
                    <svg class="document-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm0 2h12v8H4V6z" clip-rule="evenodd"/>
                    </svg>
                    <strong>Carta Compromiso</strong>
                </li>
                <li>
                    <svg class="document-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm0 2h12v8H4V6z" clip-rule="evenodd"/>
                    </svg>
                    <strong>Aviso de Privacidad</strong>
                </li>
                <li>
                    <svg class="document-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm0 2h12v8H4V6z" clip-rule="evenodd"/>
                    </svg>
                    <strong>Checklist Completo</strong>&nbsp;(para más adelante)
                </li>
            </ul>
        </div>

        <div style="background: #eef7e6; border-left: 4px solid #7DC142; padding: 15px 20px; border-radius: 8px; margin: 25px 0;">
            <p style="margin: 0; color: #2d5a15; font-size: 14.5px; line-height: 1.5;">
                ✍️ <strong>Para firmar:</strong> Puede llenar y firmar el documento de forma digital y devolvérnoslo firmado —no necesita ninguna liga ni herramienta especial—. Cualquier duda, con gusto le apoyamos.
            </p>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="mailto:convenios@xante.mx?subject=Convenio Firmado - {{ $clientName }}" class="btn">
                Enviar Convenio Firmado
            </a>
        </div>
    </div>

    <div class="footer">
        <p><strong>XANTE.MX</strong> - Su socio de confianza en bienes raíces</p>
        <div class="contact-info">
            <p>📧 Email: convenios@xante.mx | 📞 Teléfono: +52 (55) 7931-8910</p>
            <p>🌐 Sitio web: www.xante.mx</p>
            <p style="font-size: 12px; color: #999; margin-top: 15px;">
                Este correo fue generado automáticamente. Por favor, no responda a esta dirección.
                <br>
                Para consultas, utilice los medios de contacto proporcionados arriba.
            </p>
        </div>
    </div>
</body>
</html>
