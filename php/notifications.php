<?php

function notificationChannels(): array
{
    global $appConfig;
    $from = trim((string) ($appConfig['mailFromAddress'] ?? ''));
    $host = trim((string) ($appConfig['mailHost'] ?? ''));
    return [
        'email' => [
            'available' => $host !== '' && filter_var($from, FILTER_VALIDATE_EMAIL) !== false,
            'testMode' => !empty($appConfig['mailTestMode']),
        ],
        'sms' => ['available' => false],
        'whatsapp' => ['available' => false],
    ];
}

function sendResidentNotification(string $method, string $recipient, string $referenceCode): array
{
    if ($method === 'email') {
        return sendReferenceEmail($recipient, $referenceCode);
    }
    if ($method === 'phone') {
        return [
            'sent' => false,
            'message' => 'Your report was saved, but SMS delivery is not configured. Save the reference code below.',
        ];
    }
    if ($method === 'whatsapp') {
        return [
            'sent' => false,
            'message' => 'Your report was saved, but WhatsApp delivery is not configured. Save the reference code below.',
        ];
    }
    return [
        'sent' => false,
        'message' => 'Your report was saved. No notification method was selected, so save the reference code below.',
    ];
}

function sendReferenceEmail(string $recipient, string $referenceCode): array
{
    global $appConfig;
    $channels = notificationChannels();
    if (!$channels['email']['available']) {
        return [
            'sent' => false,
            'message' => 'Your report was saved, but email delivery is not configured. Save the reference code below.',
        ];
    }

    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        error_log('Reference email could not be sent because Composer dependencies are missing.');
        return [
            'sent' => false,
            'message' => 'Your report was saved, but email delivery is unavailable. Save the reference code below.',
        ];
    }
    require_once $autoload;

    $username = trim((string) ($appConfig['mailUsername'] ?? ''));
    $password = (string) ($appConfig['mailPassword'] ?? '');
    if (($username === '') !== ($password === '')) {
        error_log('Reference email could not be sent because SMTP authentication settings are incomplete.');
        return [
            'sent' => false,
            'message' => 'Your report was saved, but email delivery is unavailable. Save the reference code below.',
        ];
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = trim((string) $appConfig['mailHost']);
        $mail->Port = (int) ($appConfig['mailPort'] ?? 587);
        $mail->SMTPAuth = $username !== '';
        if ($mail->SMTPAuth) {
            $mail->Username = $username;
            $mail->Password = $password;
        }
        $encryption = strtolower((string) ($appConfig['mailEncryption'] ?? 'tls'));
        if ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption !== 'none') {
            error_log('Reference email could not be sent because the SMTP encryption setting is invalid.');
            return [
                'sent' => false,
                'message' => 'Your report was saved, but email delivery is unavailable. Save the reference code below.',
            ];
        }
        $mail->Timeout = 8;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(
            (string) $appConfig['mailFromAddress'],
            (string) ($appConfig['mailFromName'] ?? 'Local Infrastructure Issue Reporter')
        );
        $mail->addAddress($recipient);
        $mail->Subject = 'Your infrastructure report reference code';
        $safeCode = htmlspecialchars($referenceCode, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $appUrl = rtrim((string) ($appConfig['appUrl'] ?? ''), '/');
        $statusLink = $appUrl !== '' && filter_var($appUrl, FILTER_VALIDATE_URL)
            ? '<p>Check progress: <a href="' . htmlspecialchars($appUrl . '/status.html', ENT_QUOTES, 'UTF-8') . '">Check report status</a></p>'
            : '';
        $mail->isHTML(true);
        $mail->Body = '<p>Your infrastructure report was received.</p><p>Reference code: <strong>' . $safeCode . '</strong></p>' . $statusLink;
        $mail->AltBody = "Your infrastructure report was received.\nReference code: $referenceCode"
            . ($statusLink !== '' ? "\nCheck progress at $appUrl/status.html" : '');
        $mail->send();
    } catch (PHPMailer\PHPMailer\Exception $exception) {
        error_log('Reference email could not be sent by the configured SMTP server.');
        return [
            'sent' => false,
            'message' => 'Your report was saved, but email could not be sent. Save the reference code below.',
        ];
    }

    if ($channels['email']['testMode']) {
        return [
            'sent' => true,
            'message' => 'A test email was captured in the local test inbox. It was not delivered to your real email account.',
        ];
    }
    return [
        'sent' => true,
        'message' => 'Your email provider accepted a reference-code message for delivery.',
    ];
}
