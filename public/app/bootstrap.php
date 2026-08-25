<?php
define('ROOT_DIR', dirname(__DIR__));
define('APP_DIR', ROOT_DIR . '/app');
define('DATA_DIR', ROOT_DIR . '/data');
define('UPLOAD_DIR', ROOT_DIR . '/uploads/foods');

if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

require APP_DIR . '/helpers.php';
require APP_DIR . '/Database.php';
require APP_DIR . '/Auth.php';
require APP_DIR . '/Uploads.php';
require APP_DIR . '/Foods.php';
require APP_DIR . '/seed.php';

load_env(dirname(ROOT_DIR) . '/.env');
load_env(ROOT_DIR . '/.env');

$config = app_config();

session_name('maha_session');
session_set_cookie_params(array(
    'lifetime' => 7 * 24 * 60 * 60,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($config['cookie_secure'])
));
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

try {
    $db = Database::connect($config);
    migrate($db, $config['db_connection']);
    seed_database($db, $config);
} catch (Exception $e) {
    json_error(500, 'اتصال به دیتابیس برقرار نشد. فایل .env را بررسی کنید.');
}
