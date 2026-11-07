<?php

$server = stream_socket_server('tcp://127.0.0.1:' . getenv('HYBRIDAUTH_PROXY_PORT'));
while (true) {
    $client = @stream_socket_accept($server, 30);
    if (!$client) { continue; }
    stream_set_timeout($client, 5);
    $request = fgets($client);
    if (!$request) { fclose($client); continue; }
    $headers = [];
    while (($line = fgets($client)) !== false && trim($line) !== '') { $headers[] = trim($line); }
    $authenticated = in_array('Proxy-Authorization: Basic ' . base64_encode('demo:proxy:secret'), $headers, true);
    file_put_contents(getenv('HYBRIDAUTH_PROXY_LOG'), json_encode([
        'request' => trim($request), 'authenticated' => $authenticated,
    ]) . "\n", FILE_APPEND);
    if (!preg_match('/^CONNECT 127\.0\.0\.1:(\d+) HTTP\/1\.1$/D', trim($request), $match)
        || !in_array($match[1], explode(',', getenv('HYBRIDAUTH_TARGET_PORT')), true)
    ) {
        fwrite($client, "HTTP/1.1 403 Forbidden\r\nContent-Length: 0\r\n\r\n");
        fclose($client);
        continue;
    }
    $destination = '127.0.0.1:' . $match[1];
    $upstream = stream_socket_client('tcp://' . $destination, $errno, $error, 5);
    if (!$upstream) { fclose($client); continue; }
    fwrite($client, "HTTP/1.1 200 Connection Established\r\nX-XRDS-Location: http://127.0.0.1/proxy-trap\r\n\r\n");
    stream_set_blocking($client, false);
    stream_set_blocking($upstream, false);
    while (!feof($client) && !feof($upstream)) {
        $read = [$client, $upstream]; $write = null; $except = null;
        if (!stream_select($read, $write, $except, 5)) { break; }
        foreach ($read as $source) {
            $bytes = fread($source, 8192);
            $target = $source === $client ? $upstream : $client;
            while ($bytes !== '') {
                $written = fwrite($target, $bytes);
                if (!$written) { break 3; }
                $bytes = substr($bytes, $written);
            }
        }
    }
    fclose($upstream);
    fclose($client);
}
