<?php

function load_env($path)
{
    if (!is_file($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        $value = trim($value, "\"'");
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
    }
}

function env_val($name, $default = '')
{
    $value = getenv($name);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}

function app_config()
{
    return array(
        'db_connection' => env_val('DB_CONNECTION', 'sqlite'),
        'db_path' => env_val('DB_PATH', DATA_DIR . '/maha.db'),
        'db_host' => env_val('DB_HOST', 'localhost'),
        'db_port' => env_val('DB_PORT', '3306'),
        'db_name' => env_val('DB_NAME', ''),
        'db_user' => env_val('DB_USER', ''),
        'db_pass' => env_val('DB_PASS', ''),
        'admin_username' => env_val('ADMIN_USERNAME', 'admin'),
        'admin_password' => env_val('ADMIN_PASSWORD', '8242026'),
        'cookie_secure' => env_val('COOKIE_SECURE', 'false') === 'true',
        'max_file_size' => 5 * 1024 * 1024
    );
}

function json_ok($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error($status, $message)
{
    json_ok(array('message' => $message), $status);
}

function request_method()
{
    $method = strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET');
    if ($method === 'POST') {
        if (!empty($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        } elseif (!empty($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }
    }
    return $method;
}

function request_path()
{
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    $q = strpos($uri, '?');
    if ($q !== false) {
        $uri = substr($uri, 0, $q);
    }
    $uri = rawurldecode($uri);
    if ($uri === '/api') {
        return '/';
    }
    if (strpos($uri, '/api/') === 0) {
        return substr($uri, 4);
    }
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    if ($script && strpos($uri, $script) === 0) {
        $uri = substr($uri, strlen($script));
    }
    return $uri === '' ? '/' : $uri;
}

function request_body()
{
    static $body = null;
    if ($body !== null) {
        return $body;
    }
    $body = array();
    if (!empty($_POST)) {
        $body = $_POST;
    }
    $raw = file_get_contents('php://input');
    if ($raw) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $body = array_merge($body, $json);
        }
    }
    return $body;
}

function input($key, $default = null)
{
    $body = request_body();
    if (array_key_exists($key, $body)) {
        return $body[$key];
    }
    if (array_key_exists($key, $_GET)) {
        return $_GET[$key];
    }
    return $default;
}

function trim_str($value, $max = 0)
{
    $text = trim((string) $value);
    if ($max > 0) {
        if (function_exists('mb_substr')) {
            $text = mb_substr($text, 0, $max, 'UTF-8');
        } else {
            $text = substr($text, 0, $max);
        }
    }
    return $text;
}

function to_int($value, $fallback = 0)
{
    if ($value === null || $value === '') {
        return $fallback;
    }
    if (!is_numeric($value)) {
        return $fallback;
    }
    return (int) round($value);
}

function to_bool($value, $fallback = false)
{
    if ($value === null || $value === '') {
        return $fallback;
    }
    if (is_bool($value)) {
        return $value;
    }
    if (is_numeric($value)) {
        return (int) $value === 1;
    }
    $normalized = strtolower((string) $value);
    return $normalized === '1' || $normalized === 'true' || $normalized === 'on';
}

function parse_sizes($input)
{
    if (!$input) {
        return array();
    }
    $parsed = $input;
    if (is_string($input)) {
        $parsed = json_decode($input, true);
        if (!is_array($parsed)) {
            return array();
        }
    }
    if (!is_array($parsed)) {
        return array();
    }
    $sizes = array();
    foreach ($parsed as $index => $item) {
        if (isset($item[0])) {
            $label = trim_str($item[0], 40);
            $price = to_int(isset($item[1]) ? $item[1] : 0, 0);
        } else {
            $label = trim_str(isset($item['label']) ? $item['label'] : (isset($item['name']) ? $item['name'] : ''), 40);
            $price = to_int(isset($item['price']) ? $item['price'] : 0, 0);
        }
        if ($label === '') {
            continue;
        }
        $sizes[] = array(
            'label' => $label,
            'price' => $price,
            'sortOrder' => $index + 1
        );
    }
    return $sizes;
}

function client_ip()
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    }
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

function login_allowed($ip)
{
    $file = DATA_DIR . '/login_attempts.json';
    $now = time();
    $data = array();
    if (is_file($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data)) {
            $data = array();
        }
    }
    $recent = array();
    if (isset($data[$ip]) && is_array($data[$ip])) {
        foreach ($data[$ip] as $ts) {
            if ($now - (int) $ts < 15 * 60) {
                $recent[] = (int) $ts;
            }
        }
    }
    if (count($recent) >= 10) {
        return false;
    }
    $recent[] = $now;
    $data[$ip] = $recent;
    file_put_contents($file, json_encode($data));
    return true;
}

function bool_json($value)
{
    return (int) $value === 1;
}
