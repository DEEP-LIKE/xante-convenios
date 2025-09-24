<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convenio de Compraventa</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .title {
            font-size: 20px;
            color: #333;
            margin-bottom: 10px;
        }
        .content {
            margin-bottom: 25px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .info-value {
            color: #333;
            font-weight: 500;
        }
        .total-amount {
            background-color: #28a745;
            color: white;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin: 20px 0;
        }
        .total-amount .amount {
            font-size: 24px;
            font-weight: bold;
        }
        .next-steps {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .next-steps h3 {
            color: #856404;
            margin-top: 0;
        }
        .next-steps ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .next-steps li {
            margin-bottom: 5px;
            color: #856404;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 14px;
        }
        .contact-info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .contact-info h3 {
            margin-top: 0;
            color: #495057;
        }
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 10px 0;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">XANTE.MX</div>
            <div class="title">Convenio de Compraventa Generado</div>
        </div>

        <div class="content">
            <div class="greeting">
                Estimado/a <strong>{{ $clientName }}</strong>,
            </div>

            <p>Nos complace informarle que su convenio de compraventa ha sido generado exitosamente. Adjunto a este correo encontrará el documento PDF con todos los detalles de la transacción.</p>

            <div class="info-box">
                <h3 style="margin-top: 0; color: #007bff;">Detalles del Convenio</h3>
                <div class="info-row">
                    <span class="info-label">Número de Convenio:</span>
                    <span class="info-value">#{{ str_pad($agreement->id, 6, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha de Generación:</span>
                    <span class="info-value">{{ $agreement->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Propiedad:</span>
                    <span class="info-value">{{ $propertyAddress }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Cliente:</span>
                    <span class="info-value">{{ $clientName }}</span>
                </div>
            </div>

            <div class="total-amount">
                <div>Monto Total del Convenio</div>
                <div class="amount">${{ number_format($totalPayment, 2) }} MXN</div>
            </div>

            <div class="next-steps">
                <h3>Próximos Pasos</h3>
                <ul>
                    <li>Revise cuidadosamente el documento PDF adjunto</li>
                    <li>Verifique que todos los datos sean correctos</li>
                    <li>Si encuentra algún error, contacte inmediatamente a nuestro equipo</li>
                    <li>Proceda con los trámites notariales según lo acordado</li>
                    <li>Mantenga este documento para sus registros</li>
                </ul>
            </div>

            <p>Si tiene alguna pregunta o necesita realizar alguna modificación, no dude en contactarnos. Nuestro equipo está disponible para asistirle en todo el proceso.</p>

            <div class="contact-info">
                <h3>Información de Contacto</h3>
                <p><strong>Email:</strong> info@xante.mx</p>
                <p><strong>Teléfono:</strong> +52 (55) 1234-5678</p>
                <p><strong>Horario de Atención:</strong> Lunes a Viernes, 9:00 AM - 6:00 PM</p>
            </div>

            <p>Agradecemos su confianza en XANTE.MX para esta importante transacción inmobiliaria.</p>

            <p>Atentamente,<br>
            <strong>El Equipo de XANTE.MX</strong></p>
        </div>

        <div class="footer">
            <p>Este es un correo automático, por favor no responda a esta dirección.</p>
            <p>© {{ date('Y') }} XANTE.MX - Todos los derechos reservados</p>
            <p>Este documento fue generado automáticamente el {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
