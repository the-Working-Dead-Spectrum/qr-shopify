<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Votre QR Code</title>
    <style>
        /* Reset + base */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            height: 100% !important;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background-color: #f4f6f8;
            color: #1a202c;
        }
        a { color: #1B56F5; text-decoration: none; }

        /* Container */
        .email-wrapper {
            width: 100%;
            background-color: #f4f6f8;
            padding: 32px 16px;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(10, 33, 100, 0.06);
        }

        /* Header */
        .email-header {
            background: linear-gradient(135deg, #0A2164 0%, #1B56F5 100%);
            padding: 32px 24px;
            text-align: center;
            color: #ffffff;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .email-header p {
            margin: 8px 0 0;
            font-size: 14px;
            opacity: 0.9;
        }

        /* Body */
        .email-body {
            padding: 40px 32px;
        }
        .email-body h2 {
            margin: 0 0 16px;
            font-size: 20px;
            font-weight: 600;
            color: #0A2164;
        }
        .email-body p {
            margin: 0 0 16px;
            font-size: 16px;
            line-height: 1.6;
            color: #4a5568;
        }
        .email-body strong {
            color: #1a202c;
        }

        /* QR section */
        .qr-section {
            text-align: center;
            margin: 32px 0;
            padding: 24px;
            background-color: #f7fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .qr-section img {
            display: block;
            margin: 0 auto;
            width: 300px;
            height: 300px;
            max-width: 100%;
            background-color: #ffffff;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .qr-caption {
            margin-top: 16px;
            font-size: 13px;
            color: #718096;
        }

        /* CTA button */
        .cta-button {
            display: inline-block;
            padding: 14px 32px;
            background-color: #1B56F5;
            color: #ffffff !important;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            margin: 16px 0;
        }
        .cta-wrapper { text-align: center; margin: 24px 0; }

        /* Info box */
        .info-box {
            background-color: #ebf4ff;
            border-left: 4px solid #1B56F5;
            padding: 16px 20px;
            border-radius: 6px;
            margin: 24px 0;
        }
        .info-box p { margin: 0; font-size: 14px; color: #2c5282; }
        .info-box strong { color: #1B56F5; }

        /* Footer */
        .email-footer {
            background-color: #f7fafc;
            padding: 24px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .email-footer p {
            margin: 4px 0;
            font-size: 13px;
            color: #718096;
            line-height: 1.5;
        }
        .email-footer a {
            color: #4a5568;
            text-decoration: underline;
        }

        /* Mobile */
        @media only screen and (max-width: 480px) {
            .email-body { padding: 24px 20px; }
            .email-header { padding: 24px 16px; }
            .email-header h1 { font-size: 20px; }
            .qr-section img { width: 240px; height: 240px; }
        }
    </style>
</head>
<body>
<div class="email-wrapper">
    <div class="email-container">

        {{-- En-tête --}}
        <div class="email-header">
            <h1>{{ $appName }}</h1>
            <p>Votre justificatif d'achat</p>
        </div>

        {{-- Corps --}}
        <div class="email-body">
            <h2>Bonjour {{ $customerName }},</h2>

            <p>
                Merci pour votre commande <strong>#{{ $orderReference }}</strong>.
                Voici votre QR Code personnel à présenter lors de votre rendez-vous.
            </p>

            <p>
                Ce QR Code est <strong>unique et à usage unique</strong>. Il ne peut être utilisé qu'une seule fois.
            </p>

            {{-- QR Code --}}
            <div class="qr-section">
                <img
                    src="data:image/png;base64,{{ $qrImageBase64 }}"
                    alt="Votre QR Code de validation"
                    width="300"
                    height="300"
                >
                <p class="qr-caption">
                    Scannez ce QR Code lors de votre rendez-vous
                </p>
            </div>

            {{-- Fallback link --}}
            <div class="cta-wrapper">
                <a href="{{ $fallbackUrl }}" class="cta-button">
                    Afficher mon QR Code
                </a>
                <p style="font-size: 13px; color: #718096; margin-top: 12px;">
                    Si l'image ne s'affiche pas, utilisez le bouton ci-dessus.
                </p>
            </div>

            {{-- Info expiration --}}
            @if($expiresAt)
                <div class="info-box">
                    <p>
                        <strong>Important :</strong>
                        Ce QR Code est valide jusqu'au <strong>{{ $expiresAt }}</strong>.
                        Au-delà, il ne pourra plus être utilisé.
                    </p>
                </div>
            @endif

            <p>
                En cas de question, contactez notre service client en répondant simplement à cet email.
            </p>

            <p style="margin-top: 24px;">
                Cordialement,<br>
                <strong>L'équipe {{ $appName }}</strong>
            </p>
        </div>

        {{-- Pied de page --}}
        <div class="email-footer">
            <p>
                Vous avez reçu cet email suite à votre achat sur notre site.
            </p>
            <p>
                <a href="{{ $fallbackUrl }}">Voir mon QR Code</a>
                ·
                <a href="mailto:{{ config('mail.from.address') }}">Nous contacter</a>
            </p>
            <p style="margin-top: 12px; font-size: 12px; color: #a0aec0;">
                © {{ date('Y') }} {{ $appName }}. Tous droits réservés.
            </p>
        </div>

    </div>
</div>
</body>
</html>