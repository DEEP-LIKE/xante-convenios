<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos de su Convenio Inmobiliario</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .content {
            background: #fff;
            padding: 30px 20px;
            border: 1px solid #ddd;
            border-top: none;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dotted #ccc;
        }
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .info-value {
            color: #007bff;
            font-weight: 600;
        }
        .documents-section {
            background: #e8f4fd;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
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
            border-left: 3px solid #28a745;
            display: flex;
            align-items: center;
        }
        .document-icon {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            fill: #28a745;
        }
        .next-steps {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .next-steps h3 {
            color: #856404;
            margin-top: 0;
        }
        .next-steps ol {
            color: #856404;
            margin: 15px 0;
            padding-left: 20px;
        }
        .next-steps li {
            margin-bottom: 8px;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            border: 1px solid #ddd;
            border-top: none;
            font-size: 14px;
            color: #666;
        }
        .contact-info {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #0056b3;
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
        
        <p>Nos complace informarle que hemos completado la preparación de todos los documentos relacionados con su convenio inmobiliario. Los documentos han sido generados exitosamente y se encuentran adjuntos a este correo electrónico.</p>

        <div class="info-box">
            <h3 style="margin-top: 0; color: #007bff;">📋 Información del Convenio</h3>
            <div class="info-row">
                <span class="info-label">Propiedad:</span>
                <span class="info-value">{{ $propertyAddress }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Valor del Convenio:</span>
                <span class="info-value">${{ $valorConvenio }} MXN</span>
            </div>
            <div class="info-row">
                <span class="info-label">Ganancia Final Estimada:</span>
                <span class="info-value">${{ $gananciaFinal }} MXN</span>
            </div>
            <div class="info-row">
                <span class="info-label">Fecha de Generación:</span>
                <span class="info-value">{{ now()->format('d/m/Y H:i') }}</span>
            </div>
        </div>

        <div class="documents-section">
            <h3 style="margin-top: 0; color: #0056b3;">📎 Documentos Adjuntos</h3>
            <p>Los siguientes documentos se encuentran adjuntos a este correo:</p>
            <ul class="document-list">
                @foreach($agreement->generatedDocuments as $document)
                <li>
                    <svg class="document-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm0 2h12v8H4V6z" clip-rule="evenodd"/>
                    </svg>
                    {{ $document->formatted_type }}
                </li>
                @endforeach
            </ul>
        </div>

        <div class="next-steps">
            <h3>🔄 Próximos Pasos</h3>
            <p>Para continuar con el proceso de su convenio, necesitamos que nos proporcione la siguiente documentación:</p>
            <ol>
                <li><strong>Documentación Personal:</strong> INE, CURP, RFC, Constancia de Situación Fiscal</li>
                <li><strong>Comprobantes:</strong> Domicilio actual, Estado de cuenta bancario</li>
                <li><strong>Documentación de la Propiedad:</strong> Escrituras, Recibos de servicios actuales</li>
                <li><strong>Documentos Adicionales:</strong> Acta de nacimiento, Acta de matrimonio (si aplica)</li>
            </ol>
            <p><strong>Importante:</strong> Nuestro equipo se pondrá en contacto con usted para coordinar la entrega de estos documentos y resolver cualquier duda que pueda tener.</p>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="mailto:info@xante.mx?subject=Consulta sobre Convenio {{ $agreement->id }}" class="btn">
                Contactar a XANTE
            </a>
            <a href="tel:+525512345678" class="btn" style="background: #28a745;">
                Llamar Ahora
            </a>
        </div>

        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h4 style="margin-top: 0; color: #495057;">💡 Consejos Importantes</h4>
            <ul style="color: #6c757d; margin: 0;">
                <li>Revise cuidadosamente todos los documentos adjuntos</li>
                <li>Mantenga estos documentos en un lugar seguro</li>
                <li>Si tiene alguna pregunta, no dude en contactarnos</li>
                <li>Prepare la documentación solicitada para agilizar el proceso</li>
            </ul>
        </div>
    </div>

    <div class="footer">
        <p><strong>XANTE.MX</strong> - Su socio de confianza en bienes raíces</p>
        <div class="contact-info">
            <p>📧 Email: info@xante.mx | 📞 Teléfono: +52 (55) 1234-5678</p>
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
