<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($uri === '/api' || strpos($uri, '/api/') === 0) {
    require __DIR__ . '/api/index.php';
    return true;
}
$file = __DIR__ . $uri;
if ($uri !== '/' && is_file($file)) {
    return false;
}
if ($uri !== '/' && !is_file($file)) {
    http_response_code(404);
    require __DIR__ . '/404.html';
    return true;
}
return false;
