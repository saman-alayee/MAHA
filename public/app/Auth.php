<?php

function current_admin(Database $db)
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    $row = $db->fetch('SELECT id, username FROM admins WHERE id = ?', array($_SESSION['admin_id']));
    return $row ? $row : null;
}

function require_admin(Database $db)
{
    $admin = current_admin($db);
    if (!$admin) {
        json_error(401, 'وارد پنل نشده‌اید');
    }
    return $admin;
}

function login_admin($admin)
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
}

function logout_admin()
{
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
