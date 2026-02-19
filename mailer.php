<?php
$mailConfig = include 'config.php';
if (file_exists('config.local.php')) {
    $localConfig = include '/config.local.php';
    if (is_array($localConfig)) {
        $mailConfig = array_merge($mailConfig, array_filter($localConfig, fn($v) => $v !== ''));
    }
}
function smtpLireReponse($socket)
{
    $data = '';
    while ($ligne = fgets($socket, 515)) {
        $data .= $ligne;
        if (preg_match('/^\d{3} /', $ligne)) {
            break;
        }
    }
    return $data;
}
function smtpCommande($socket, $commande, $codeAttendu)
{
    fwrite($socket, $commande . "\r\n");
    $reponse = smtpLireReponse($socket);
    return strpos($reponse, (string) $codeAttendu) === 0;
}
function envoyerMailSmtp($destinataire, $sujet, $message)
{
    global $mailConfig;
    if (empty($mailConfig['smtp_user']) || empty($mailConfig['smtp_pass'])) {
        return false;
    }
    $host = $mailConfig['smtp_host'];
    $port = (int) $mailConfig['smtp_port'];
    $secure = $mailConfig['smtp_secure'];
    $socket = fsockopen($host, $port, $errno, $errstr, 10);
    if (!$socket) {
        return false;
    }
    smtpLireReponse($socket);
    if (!smtpCommande($socket, 'EHLO localhost', 250)) {
        fclose($socket);
        return false;
    }
    if ($secure === 'tls') {
        if (!smtpCommande($socket, 'STARTTLS', 220)) {
            fclose($socket);
            return false;
        }
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if (!smtpCommande($socket, 'EHLO localhost', 250)) {
            fclose($socket);
            return false;
        }
    }
    if (!smtpCommande($socket, 'AUTH LOGIN', 334)) {
        fclose($socket);
        return false;
    }
    if (!smtpCommande($socket, base64_encode($mailConfig['smtp_user']), 334)) {
        fclose($socket);
        return false;
    }
    if (!smtpCommande($socket, base64_encode($mailConfig['smtp_pass']), 235)) {
        fclose($socket);
        return false;
    }
    $fromEmail = $mailConfig['from_email'] ?: $mailConfig['smtp_user'];
    if (!smtpCommande($socket, "MAIL FROM:<{$fromEmail}>", 250)) {
        fclose($socket);
        return false;
    }
    if (!smtpCommande($socket, "RCPT TO:<{$destinataire}>", 250)) {
        fclose($socket);
        return false;
    }
    if (!smtpCommande($socket, 'DATA', 354)) {
        fclose($socket);
        return false;
    }
    $fromName = $mailConfig['from_name'];
    $replyTo = $mailConfig['reply_to'] ?? '';
    $subjectEncoded = '=?UTF-8?B?' . base64_encode($sujet) . '?=';
    $headers = "From: \"{$fromName}\" <{$fromEmail}>\r\n";
    if (!empty($replyTo)) {
        $headers .= "Reply-To: <{$replyTo}>\r\n";
    }
    $headers .= "To: <{$destinataire}>\r\n";
    $headers .= "Subject: {$subjectEncoded}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $contenu = $headers . "\r\n" . $message;
    fwrite($socket, $contenu . "\r\n.\r\n");
    smtpLireReponse($socket);
    smtpCommande($socket, 'QUIT', 221);
    fclose($socket);
    return true;
}