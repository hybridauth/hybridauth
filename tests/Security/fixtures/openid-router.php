<?php

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
file_put_contents(getenv('HYBRIDAUTH_TEST_LOG'), $_SERVER['REQUEST_METHOD'] . ' ' . $path . "\n", FILE_APPEND);
if ($path === '/redirect') {
    header('Location: /identity');
} elseif ($path === '/private-redirect') {
    header('Location: http://127.0.0.1/private');
} elseif ($path === '/post307') {
    header('Location: /echo', true, 307);
} elseif ($path === '/head405' && $_SERVER['REQUEST_METHOD'] === 'HEAD') {
    http_response_code(405);
} elseif ($path === '/echo') {
    header('Content-Type: application/json');
    echo json_encode(['host' => $_SERVER['HTTP_HOST'], 'method' => $_SERVER['REQUEST_METHOD'],
        'body' => file_get_contents('php://input'), 'query' => $_GET,
        'proxy_auth' => isset($_SERVER['HTTP_PROXY_AUTHORIZATION'])]);
} elseif ($path === '/verify') {
    header('Content-Type: text/plain');
    echo "is_valid:true\n";
} elseif ($path === '/corporate-identity') {
    header('Content-Type: text/html');
    echo '<link rel="openid2.provider" href="http://' . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_QUOTES, 'UTF-8') . '/verify">';
} else {
    header('Content-Type: text/html');
    echo '<link rel="openid2.provider" href="http://' . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_QUOTES, 'UTF-8') . '/echo">';
}
