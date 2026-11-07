<?php

$context = stream_context_create(['ssl' => [
    'local_cert' => getenv('HYBRIDAUTH_TLS_CERT'), 'local_pk' => getenv('HYBRIDAUTH_TLS_KEY'),
    'verify_peer' => false,
]]);
$server = stream_socket_server('tcp://127.0.0.1:' . getenv('HYBRIDAUTH_TLS_PORT'), $errno, $error,
    STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
while ($client = @stream_socket_accept($server, 30)) {
    stream_set_timeout($client, 3);
    if (!@stream_socket_enable_crypto($client, true, STREAM_CRYPTO_METHOD_TLS_SERVER)) {
        fclose($client);
        continue;
    }
    fgets($client);
    $host = '';
    while (($line = fgets($client)) !== false && trim($line) !== '') {
        if (stripos($line, 'Host:') === 0) { $host = trim(substr($line, 5)); }
    }
    $body = json_encode(['host' => $host]);
    fwrite($client, "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nConnection: close\r\nContent-Length: "
        . strlen($body) . "\r\n\r\n" . $body);
    fclose($client);
}
