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
            background: linear-gradient(135deg, #6C2582, #7C4794); /* Morado Principal a Morado Medio */
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
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
            <!-- <div class="info-row">
                <span class="info-label">Ganancia Final Estimada:</span>
                <span class="info-value">${{ $gananciaFinal }} MXN</span>
            </div>  -->
            <div class="info-row">
                <span class="info-label">Fecha de Generación:</span>
                <span class="info-value">{{ now()->format('d/m/Y H:i') }}</span>
            </div>
        </div> 

        <div class="documents-section">
            <h3 style="margin-top: 0; color: #0056b3;">📎 Documentos Generados</h3>
            <p>Se han generado los siguientes documentos para su convenio:</p>
            <ul class="document-list">
                @foreach($agreement->generatedDocuments as $document)
                <li>
                    <svg class="document-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm0 2h12v8H4V6z" clip-rule="evenodd"/>
                    </svg>
                    {{ $document->formatted_type }}
                    @php 
                        $tooLarge = false;
                        try {
                            $tooLarge = \Storage::disk('s3')->size($document->file_path) > 4 * 1024 * 1024;
                        } catch (\Exception $e) {
                            \Log::warning('Error checking file size in email view', ['path' => $document->file_path, 'error' => $e->getMessage()]);
                        }
                    @endphp
                    @if($tooLarge)
                        <span style="color: #dc3545; font-size: 12px; margin-left: 10px;">(Archivo grande - disponible por descarga directa)</span>
                    @endif
                </li>
                @endforeach
            </ul>
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin-top: 15px;">
                <p style="margin: 0; color: #856404; font-size: 14px;">
                    <strong>📋 Nota:</strong> Los documentos más pequeños se encuentran adjuntos a este correo. 
                    Los archivos de mayor tamaño estarán disponibles para descarga directa a través de nuestro asesor.
                </p>
            </div>
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
