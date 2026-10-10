<?php
declare(strict_types=1);

require_once __DIR__ . '/mail_config.php';

function sendShopFlowEmail(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlContent
): bool {
    $payload = [
        'sender' => [
            'name' => BREVO_SENDER_NAME,
            'email' => BREVO_SENDER_EMAIL,
        ],
        'to' => [[
            'email' => $toEmail,
            'name' => $toName,
        ]],
        'subject' => $subject,
        'htmlContent' => $htmlContent,
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'api-key: ' . BREVO_API_KEY,
            'content-type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false || $curlError !== '') {
        error_log('ShopFlow email transport failed: ' . $curlError);
        return false;
    }

    if ($statusCode < 200 || $statusCode >= 300) {
        error_log('Brevo email API returned HTTP ' . $statusCode);
        return false;
    }

    return true;
}
