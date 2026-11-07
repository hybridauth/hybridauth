<?php

header('Content-Type: application/json');
if (isset($_GET['extra'])) {
    header('X-Previous-Response: present');
}
if (isset($_GET['redirect'])) {
    header('X-Redirect-Only: present');
    header('Location: /echo');
    exit;
}
echo json_encode([
    'method' => $_SERVER['REQUEST_METHOD'],
    'host' => $_SERVER['HTTP_HOST'],
    'authorization' => isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : null,
    'content_type' => isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : null,
    'body' => file_get_contents('php://input'),
    'custom_header' => isset($_SERVER['HTTP_X_CUSTOM_HEADER']) ? $_SERVER['HTTP_X_CUSTOM_HEADER'] : null,
    'query' => $_GET,
]);
