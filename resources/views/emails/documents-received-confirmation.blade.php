<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convenio Completado - Documentos Recibidos</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8fafc;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #6C2582 0%, #7C4794 100%);
            border-radius: 8px;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        .success-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            margin-bottom: 25px;
        }
        .agreement-info {
            background: #f8f4ff;
            border: 1px solid #d8b4fe;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .agreement-info h3 {
            color: #6C2582;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
            border-bottom: 1px solid #e9d5ff;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #7C4794;
        }
        .info-value {
            color: #6C2582;
        }
        .documents-section {
            background: #fffbeb;
            border: 1px solid #FFD729;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .documents-section h3 {
            color: #b45309;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .document-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #fef3c7;
        }
        .document-item:last-child {
            border-bottom: none;
        }
        .document-icon {
            margin-right: 10px;
            font-size: 16px;
        }
        .document-name {
            font-weight: 500;
            color: #92400e;
        }
        .thank-you {
            background: #f0f9ff;
            border: 1px solid #BDCE0F;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }
        .thank-you h3 {
            color: #6C2582;
            margin-top: 0;
            font-size: 20px;
        }
        .thank-you p {
            color: #7C4794;
            font-size: 16px;
            margin-bottom: 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #6C2582;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="success-icon">🎉</div>
            <h1>¡Convenio Completado Exitosamente!</h1>
            <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">
                Todos los documentos han sido recibidos y procesados
            </p>
        </div>

        <div class="content">
            <p>Estimado/a cliente,</p>
            
            <p>
                Nos complace informarle que su convenio ha sido <strong>completado exitosamente</strong>. 
                Hemos recibido y procesado todos los documentos requeridos de manera satisfactoria.
            </p>

            <div class="agreement-info">
                <h3>📋 Información del Convenio</h3>
                <div class="info-row">
                    <span class="info-label">Cliente:</span>
                    <span class="info-value">
                        @if($agreement->wizard_data && isset($agreement->wizard_data['client_name']))
                            {{ $agreement->wizard_data['client_name'] }}
                        @else
                            {{ $agreement->client->name ?? 'No disponible' }}
                        @endif
                    </span>
                </div>
                <!-- <div class="info-row">
                    <span class="info-label">Propiedad:</span>
                    <span class="info-value">
                        @if($agreement->wizard_data && isset($agreement->wizard_data['property_address']))
                            {{ $agreement->wizard_data['property_address'] }}
                        @else
                            No disponible
                        @endif
                    </span>
                </div> -->
                <!-- <div class="info-row">
                    <span class="info-label">Valor del Convenio:</span>
                    <span class="info-value">
                        @if($agreement->wizard_data && isset($agreement->wizard_data['agreement_value']))
                            ${{ number_format($agreement->wizard_data['agreement_value'], 2) }}
                        @else
                            No disponible
                        @endif
                    </span>
                </div> -->
                <div class="info-row">
                    <span class="info-label">Fecha de Finalización:</span>
                    <span class="info-value">{{ $agreement->documents_received_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Etapa Actual:</span>
                    <span class="info-value" style="color: #BDCE0F; font-weight: bold;">✅ Proceso Completado Exitosamente</span>
                </div>
            </div>

            @if($clientDocuments->count() > 0)
            <div class="documents-section">
                <h3>📄 Documentos Recibidos y Procesados</h3>
                <p style="margin-bottom: 15px; color: #a16207;">
                    Los siguientes documentos han sido recibidos y validados exitosamente:
                </p>
                
                @foreach($clientDocuments as $document)
                <div class="document-item">
                    <span class="document-icon">✅</span>
                    <span class="document-name">
                        @if(!empty($document->document_name))
                            {{ $document->document_name }}
                        @elseif(!empty($document->document_type))
                            {{ ucwords(str_replace('_', ' ', $document->document_type)) }}
                        @elseif(!empty($document->file_name))
                            {{ $document->file_name }}
                        @else
                            Documento #{{ $document->id }}
                        @endif
                    </span>
                </div>
                @endforeach
                
                <p style="margin-top: 15px; font-size: 14px; color: #92400e;">
                    <strong>Total de documentos procesados:</strong> {{ $clientDocuments->count() }}
                </p>
            </div>
            @endif

            <div class="thank-you">
                <h3>¡Gracias por su confianza!</h3>
                <p>
                    Su convenio ha sido procesado exitosamente. Nuestro equipo se pondrá en contacto 
                    con usted para los siguientes pasos del proceso.
                </p>
            </div>

            <p>
                Si tiene alguna pregunta o necesita información adicional, no dude en contactarnos.
            </p>

            <p>
                Atentamente,<br>
                <strong>El equipo de Xante</strong>
            </p>
        </div>

        <div class="footer">
            <div class="logo">XANTE</div>
            <p>
                Este es un correo automático generado por el sistema de gestión de convenios.<br>
                Por favor, no responda directamente a este correo.
            </p>
        </div>
    </div>
</body>
</html>
