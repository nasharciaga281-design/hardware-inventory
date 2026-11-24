<?php

function system_email_configured(): bool
{
    $settings = require __DIR__ . '/../config/mail.php';
    $validUrl = (bool) preg_match(
        '/\Ahttps?:\/\/[A-Za-z0-9.-]+(?::\d+)?(?:\/[A-Za-z0-9._~!$&\'()*+,;=:@%\/-]*)?\z/',
        $settings['app_url']
    );
    $urlParts = $validUrl ? parse_url($settings['app_url']) : false;
    $localHttp = is_array($urlParts) &&
        ($urlParts['scheme'] ?? '') === 'http' &&
        in_array(strtolower($urlParts['host'] ?? ''), ['localhost', '127.0.0.1', '[::1]'], true);

    return $settings['host'] !== '' &&
        $settings['port'] > 0 && $settings['port'] <= 65535 &&
        $settings['username'] !== '' &&
        $settings['password'] !== '' &&
        filter_var($settings['from_email'], FILTER_VALIDATE_EMAIL) !== false &&
        in_array($settings['encryption'], ['tls', 'ssl'], true) &&
        $validUrl && (($urlParts['scheme'] ?? '') === 'https' || $localHttp);
}

function queue_system_email(
    string $recipient,
    string $subject,
    string $body,
    ?string $highlightedText = null
): void
{
    global $pdo;

    if (!system_email_configured() || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Email settings or recipient are invalid.');
    }

    $stmt = $pdo->prepare(
        "INSERT INTO system_email_outbox (recipient, subject, body, highlighted_text, available_at)
         VALUES (?, ?, ?, ?, UTC_TIMESTAMP())"
    );
    $stmt->execute([$recipient, $subject, $body, $highlightedText]);
}

function smtp_read_response($socket, array $expected): void
{
    $response = '';
    do {
        $line = fgets($socket, 1024);
        if ($line === false) {
            throw new RuntimeException('SMTP server did not respond.');
        }
        $response = $line;
    } while (isset($line[3]) && $line[3] === '-');

    $code = (int) substr($response, 0, 3);
    if (!in_array($code, $expected, true)) {
        throw new RuntimeException('SMTP server rejected a mail operation.');
    }
}

function smtp_write($socket, string $command): void
{
    $line = $command . "\r\n";
    while ($line !== '') {
        $written = fwrite($socket, $line);
        if ($written === false || $written === 0) {
            throw new RuntimeException('SMTP connection failed.');
        }
        $line = substr($line, $written);
    }
}

function email_html_body(
    string $subject,
    string $body,
    string $appUrl,
    ?string $highlightedText
): string
{
    $paragraphs = preg_split('/\n{2,}/', trim(str_replace(["\r\n", "\r"], "\n", $body)));
    $content = '';
    $actionLink = '';

    foreach ($paragraphs as $paragraph) {
        if ($paragraph === '{{REASON}}') {
            if ($highlightedText !== null) {
                $reason = nl2br(htmlspecialchars($highlightedText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
                $content .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;background:#fff7ed;border:1px solid #fed7aa;border-left:4px solid #ea580c;border-radius:6px"><tr><td style="padding:16px 18px;color:#7c2d12;font-size:14px;line-height:1.6"><strong style="display:block;margin-bottom:6px">Reason</strong>' . $reason . '</td></tr></table>';
            }
            continue;
        }

        $links = [];
        $paragraph = preg_replace_callback(
            '~https?://[^\s<]+~',
            static function (array $matches) use (&$actionLink, &$links, $appUrl): string {
                $url = rtrim($matches[0], '.,;)');
                $urlParts = parse_url($url);
                $appParts = parse_url($appUrl);
                if (!is_array($urlParts) || !is_array($appParts) ||
                    strtolower($urlParts['scheme'] ?? '') !== strtolower($appParts['scheme'] ?? '') ||
                    strtolower($urlParts['host'] ?? '') !== strtolower($appParts['host'] ?? '')) {
                    return $matches[0];
                }

                $actionLink = $url;
                $linkText = strpos($url, 'verify=') !== false
                    ? 'Verify email'
                    : (strpos($url, 'reset=') !== false ? 'Reset password' : 'Open link');
                $token = "\x1AHIMSURL" . count($links) . "\x1A";
                $links[$token] = '<a href="' .
                    htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                    '" style="color:#175cd3;text-decoration:underline">' . $linkText . '</a>';
                return $token . substr($matches[0], strlen($url));
            },
            $paragraph
        );
        $paragraph = nl2br(htmlspecialchars($paragraph, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        $content .= '<p style="margin:0 0 16px;color:#344054;font-size:15px;line-height:1.7">' .
            str_replace(array_keys($links), array_values($links), $paragraph) . '</p>';
    }

    $button = '';
    if ($actionLink !== '') {
        $label = strpos($actionLink, 'verify=') !== false
            ? 'Verify email address'
            : (strpos($actionLink, 'reset=') !== false ? 'Reset your password' : 'Continue');
        $button = '<tr><td align="center" style="padding:8px 0 24px"><a href="' .
            htmlspecialchars($actionLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
            '" style="display:inline-block;padding:13px 22px;background:#175cd3;border-radius:6px;color:#fff;font-size:14px;font-weight:bold;text-decoration:none">' .
            $label . '</a></td></tr>';
    }

    $brand = 'Hardware Inventory Management System';
    $heading = htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light"><title>' .
        $heading . '</title></head><body style="margin:0;padding:24px 12px;background:#f2f4f7;font-family:Arial,Helvetica,sans-serif"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto"><tr><td style="padding:22px 24px;background:#142b4a;border-radius:10px 10px 0 0;color:#fff;font-size:16px;font-weight:bold;line-height:1.4">' .
        $brand . '</td></tr><tr><td style="padding:30px 28px 20px;background:#fff"><h1 style="margin:0 0 22px;color:#101828;font-size:23px;line-height:1.3">' .
        $heading . '</h1>' . $content . '</td></tr>' . $button .
        '<tr><td style="padding:18px 24px;background:#f9fafb;border-top:1px solid #eaecf0;border-radius:0 0 10px 10px;color:#667085;font-size:12px;line-height:1.6">This is an automated notification from ' .
        $brand . '. If you have questions, please contact your system administrator.</td></tr></table></body></html>';
}

function deliver_system_email(
    string $recipient,
    string $subject,
    string $body,
    ?string $highlightedText = null
): void
{
    $settings = require __DIR__ . '/../config/mail.php';
    if (!system_email_configured() || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('SMTP settings or recipient are invalid.');
    }

    $address = ($settings['encryption'] === 'ssl' ? 'ssl://' : '') .
        $settings['host'] . ':' . $settings['port'];
    $context = stream_context_create([
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
    ]);
    $socket = @stream_socket_client(
        $address,
        $errorCode,
        $errorMessage,
        15,
        STREAM_CLIENT_CONNECT,
        $context
    );
    if ($socket === false) {
        throw new RuntimeException('SMTP connection could not be established.');
    }

    stream_set_timeout($socket, 15);
    try {
        smtp_read_response($socket, [220]);
        smtp_write($socket, 'EHLO hims.local');
        smtp_read_response($socket, [250]);

        if ($settings['encryption'] === 'tls') {
            smtp_write($socket, 'STARTTLS');
            smtp_read_response($socket, [220]);
            if (stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
                throw new RuntimeException('SMTP TLS negotiation failed.');
            }
            smtp_write($socket, 'EHLO hims.local');
            smtp_read_response($socket, [250]);
        }

        smtp_write($socket, 'AUTH LOGIN');
        smtp_read_response($socket, [334]);
        smtp_write($socket, base64_encode($settings['username']));
        smtp_read_response($socket, [334]);
        smtp_write($socket, base64_encode($settings['password']));
        smtp_read_response($socket, [235]);
        smtp_write($socket, 'MAIL FROM:<' . $settings['from_email'] . '>');
        smtp_read_response($socket, [250]);
        smtp_write($socket, 'RCPT TO:<' . $recipient . '>');
        smtp_read_response($socket, [250, 251]);
        smtp_write($socket, 'DATA');
        smtp_read_response($socket, [354]);

        $fromName = '=?UTF-8?B?' . base64_encode($settings['from_name']) . '?=';
        $boundary = '=_HIMS_' . bin2hex(random_bytes(16));
        $textBody = str_replace(["\r\n", "\r"], "\n", $body);
        $plainBody = str_replace(
            '{{REASON}}',
            $highlightedText !== null ? "Reason:\n" . $highlightedText : '',
            $textBody
        );
        $htmlBody = email_html_body($subject, $textBody, $settings['app_url'], $highlightedText);
        $encodedText = chunk_split(base64_encode($plainBody), 76, "\r\n");
        $encodedHtml = chunk_split(base64_encode($htmlBody), 76, "\r\n");
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $message = 'From: ' . $fromName . ' <' . $settings['from_email'] . ">\r\n" .
            'To: <' . $recipient . ">\r\n" .
            'Subject: ' . $encodedSubject . "\r\n" .
            "MIME-Version: 1.0\r\n" .
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"' . "\r\n\r\n" .
            '--' . $boundary . "\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: base64\r\n\r\n" . $encodedText .
            '--' . $boundary . "\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: base64\r\n\r\n" . $encodedHtml .
            '--' . $boundary . '--';
        $message = preg_replace('/(?m)^\./', '..', $message);
        smtp_write($socket, $message . "\r\n.");
        smtp_read_response($socket, [250]);
        smtp_write($socket, 'QUIT');
        smtp_read_response($socket, [221]);
    } finally {
        fclose($socket);
    }
}
