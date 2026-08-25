<?php

function dispatch(Database $db, $config)
{
    $method = request_method();
    $path = request_path();
    if ($path !== '/' && substr($path, -1) === '/') {
        $path = rtrim($path, '/');
    }

    if ($method === 'GET' && $path === '/health') {
        json_ok(array('ok' => true));
    }

    if ($method === 'GET' && $path === '/settings') {
        json_ok(array('settings' => get_settings_map($db)));
    }

    if ($method === 'GET' && $path === '/categories') {
        $rows = $db->fetchAll('SELECT id, name, icon, has_sizes AS hasSizes, sort_order AS sortOrder FROM categories WHERE is_active = 1 ORDER BY sort_order, id');
        $categories = array();
        foreach ($rows as $row) {
            $categories[] = map_category_row($row, false);
        }
        json_ok(array('categories' => $categories));
    }

    if ($method === 'GET' && $path === '/foods') {
        $category = trim_str(input('category', ''));
        $params = array();
        $where = 'WHERE foods.is_active = 1 AND categories.is_active = 1';
        if ($category !== '' && $category !== 'همه') {
            $where .= ' AND categories.name = ?';
            $params[] = $category;
        }
        $rows = $db->fetchAll(food_select_sql($where, 'ORDER BY foods.sort_order, foods.id'), $params);
        $foods = array();
        foreach ($rows as $row) {
            $foods[] = map_food($db, $row);
        }
        json_ok(array('foods' => $foods));
    }

    if ($method === 'GET' && preg_match('#^/foods/(\d+)$#', $path, $m)) {
        $row = $db->fetch(food_select_sql('WHERE foods.id = ? AND foods.is_active = 1 AND categories.is_active = 1'), array($m[1]));
        if (!$row) {
            json_error(404, 'غذا پیدا نشد');
        }
        json_ok(array('food' => map_food($db, $row)));
    }

    if ($method === 'POST' && $path === '/auth/login') {
        if (!login_allowed(client_ip())) {
            json_error(429, 'تعداد تلاش ورود زیاد است. کمی بعد دوباره امتحان کنید.');
        }
        $username = trim_str(input('username'), 80);
        $password = (string) input('password', '');
        if ($username === '' || $password === '') {
            json_error(400, 'نام کاربری و رمز عبور را وارد کنید');
        }
        $admin = $db->fetch('SELECT * FROM admins WHERE username = ?', array($username));
        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            json_error(401, 'نام کاربری یا رمز عبور اشتباه است');
        }
        login_admin($admin);
        json_ok(array('user' => array('id' => (int) $admin['id'], 'username' => $admin['username'])));
    }

    if ($method === 'POST' && $path === '/auth/logout') {
        logout_admin();
        json_ok(array('ok' => true));
    }

    if ($method === 'GET' && $path === '/auth/me') {
        $admin = require_admin($db);
        json_ok(array('user' => array('id' => (int) $admin['id'], 'username' => $admin['username'])));
    }

    if (strpos($path, '/admin/') !== 0) {
        json_error(404, 'مسیر مورد نظر پیدا نشد');
    }

    $admin = require_admin($db);
    $sub = substr($path, 6);

    if ($method === 'GET' && $sub === '/categories') {
        $rows = $db->fetchAll('SELECT id, name, icon, has_sizes AS hasSizes, sort_order AS sortOrder, is_active AS isActive FROM categories ORDER BY sort_order, id');
        $categories = array();
        foreach ($rows as $row) {
            $categories[] = map_category_row($row, true);
        }
        json_ok(array('categories' => $categories));
    }

    if ($method === 'POST' && $sub === '/categories') {
        $name = trim_str(input('name'), 80);
        if ($name === '') {
            json_error(400, 'نام دسته‌بندی را وارد کنید');
        }
        $max = $db->fetch('SELECT COALESCE(MAX(sort_order), 0) AS m FROM categories');
        try {
            $id = $db->exec(
                'INSERT INTO categories (name, icon, has_sizes, sort_order, is_active) VALUES (?, ?, ?, ?, ?)',
                array(
                    $name,
                    trim_str(input('icon'), 16),
                    to_bool(input('hasSizes')) ? 1 : 0,
                    to_int(input('sortOrder'), (int) $max['m'] + 1),
                    to_bool(input('isActive'), true) ? 1 : 0
                )
            );
        } catch (PDOException $e) {
            json_error(400, 'این دسته‌بندی از قبل وجود دارد');
        }
        $row = $db->fetch('SELECT id, name, icon, has_sizes AS hasSizes, sort_order AS sortOrder, is_active AS isActive FROM categories WHERE id = ?', array($id));
        json_ok(array('category' => map_category_row($row, true)), 201);
    }

    if ($method === 'PUT' && $sub === '/categories/reorder') {
        $ids = input('ids', array());
        if (!is_array($ids) || !$ids) {
            json_error(400, 'لیست مرتب‌سازی نامعتبر است');
        }
        foreach ($ids as $index => $id) {
            $db->exec('UPDATE categories SET sort_order = ? WHERE id = ?', array($index + 1, (int) $id));
        }
        json_ok(array('ok' => true));
    }

    if (preg_match('#^/categories/(\d+)$#', $sub, $m)) {
        $current = $db->fetch('SELECT * FROM categories WHERE id = ?', array($m[1]));
        if (!$current) {
            json_error(404, 'دسته‌بندی پیدا نشد');
        }
        if ($method === 'PUT') {
            $name = trim_str(input('name'), 80);
            if ($name === '') {
                $name = $current['name'];
            }
            try {
                $db->exec(
                    'UPDATE categories SET name = ?, icon = ?, has_sizes = ?, is_active = ? WHERE id = ?',
                    array(
                        $name,
                        input('icon') === null ? $current['icon'] : trim_str(input('icon'), 16),
                        input('hasSizes') === null ? $current['has_sizes'] : (to_bool(input('hasSizes')) ? 1 : 0),
                        input('isActive') === null ? $current['is_active'] : (to_bool(input('isActive')) ? 1 : 0),
                        $current['id']
                    )
                );
            } catch (PDOException $e) {
                json_error(400, 'این دسته‌بندی از قبل وجود دارد');
            }
            $row = $db->fetch('SELECT id, name, icon, has_sizes AS hasSizes, sort_order AS sortOrder, is_active AS isActive FROM categories WHERE id = ?', array($current['id']));
            json_ok(array('category' => map_category_row($row, true)));
        }
        if ($method === 'DELETE') {
            $used = $db->fetch('SELECT COUNT(*) AS c FROM foods WHERE category_id = ?', array($current['id']));
            if ((int) $used['c'] > 0) {
                json_error(400, 'ابتدا غذاهای این دسته را حذف یا به دسته دیگری منتقل کنید');
            }
            $db->exec('DELETE FROM categories WHERE id = ?', array($current['id']));
            json_ok(array('ok' => true));
        }
    }

    if ($method === 'GET' && $sub === '/foods') {
        $rows = $db->fetchAll(food_select_sql('', 'ORDER BY foods.sort_order, foods.id'));
        $foods = array();
        foreach ($rows as $row) {
            $foods[] = map_food($db, $row);
        }
        json_ok(array('foods' => $foods));
    }

    if ($method === 'GET' && preg_match('#^/foods/(\d+)$#', $sub, $m)) {
        json_ok(array('food' => get_admin_food($db, $m[1])));
    }

    if ($method === 'POST' && $sub === '/foods') {
        $name = trim_str(input('name'), 120);
        $categoryId = to_int(input('categoryId', input('category_id')));
        $description = trim_str(input('description'), 2000);
        if ($name === '') {
            json_error(400, 'نام غذا را وارد کنید');
        }
        $category = $db->fetch('SELECT * FROM categories WHERE id = ?', array($categoryId));
        if (!$category) {
            json_error(400, 'دسته‌بندی معتبر نیست');
        }
        $image = handle_food_upload($config);
        if ($image === '') {
            $image = trim_str(input('image'), 500);
        }
        $sizes = parse_sizes(input('sizes'));
        $price = $sizes ? null : to_int(input('price'), 0);
        $max = $db->fetch('SELECT COALESCE(MAX(sort_order), 0) AS m FROM foods');
        $id = $db->exec(
            'INSERT INTO foods (name, category_id, description, image, price, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)',
            array(
                $name,
                $category['id'],
                $description,
                $image,
                $price,
                to_bool(input('isActive'), true) ? 1 : 0,
                to_int(input('sortOrder'), (int) $max['m'] + 1)
            )
        );
        if ($sizes) {
            save_food_sizes($db, $id, $sizes);
        }
        json_ok(array('food' => get_admin_food($db, $id)), 201);
    }

    if ($method === 'PUT' && $sub === '/foods/reorder') {
        $ids = input('ids', array());
        if (!is_array($ids) || !$ids) {
            json_error(400, 'لیست مرتب‌سازی نامعتبر است');
        }
        foreach ($ids as $index => $id) {
            $db->exec('UPDATE foods SET sort_order = ?, updated_at = ' . $db->nowFn() . ' WHERE id = ?', array($index + 1, (int) $id));
        }
        json_ok(array('ok' => true));
    }

    if ($method === 'PUT' && preg_match('#^/foods/(\d+)$#', $sub, $m)) {
        $current = $db->fetch('SELECT * FROM foods WHERE id = ?', array($m[1]));
        if (!$current) {
            json_error(404, 'غذا پیدا نشد');
        }
        $name = trim_str(input('name'), 120);
        if ($name === '') {
            $name = $current['name'];
        }
        $categoryId = to_int(input('categoryId', input('category_id')), $current['category_id']);
        $category = $db->fetch('SELECT * FROM categories WHERE id = ?', array($categoryId));
        if (!$category) {
            json_error(400, 'دسته‌بندی معتبر نیست');
        }
        $uploaded = handle_food_upload($config);
        $image = $current['image'];
        if ($uploaded) {
            delete_uploaded_file($current['image']);
            $image = $uploaded;
        } elseif (input('image') !== null && trim_str(input('image'), 500) !== '') {
            $image = trim_str(input('image'), 500);
        }
        $sizes = input('sizes') === null ? null : parse_sizes(input('sizes'));
        $price = ($sizes && count($sizes))
            ? null
            : (input('price') === null ? $current['price'] : to_int(input('price'), 0));
        $description = input('description') === null ? $current['description'] : trim_str(input('description'), 2000);
        $isActive = input('isActive') === null ? $current['is_active'] : (to_bool(input('isActive')) ? 1 : 0);
        $db->exec(
            'UPDATE foods SET name = ?, category_id = ?, description = ?, image = ?, price = ?, is_active = ?, updated_at = ' . $db->nowFn() . ' WHERE id = ?',
            array($name, $category['id'], $description, $image, $price, $isActive, $current['id'])
        );
        if ($sizes !== null) {
            save_food_sizes($db, $current['id'], $sizes);
        }
        json_ok(array('food' => get_admin_food($db, $current['id'])));
    }

    if ($method === 'PATCH' && preg_match('#^/foods/(\d+)$#', $sub, $m)) {
        $current = $db->fetch('SELECT * FROM foods WHERE id = ?', array($m[1]));
        if (!$current) {
            json_error(404, 'غذا پیدا نشد');
        }
        $isActive = to_bool(input('isActive'), !(int) $current['is_active']);
        $db->exec('UPDATE foods SET is_active = ?, updated_at = ' . $db->nowFn() . ' WHERE id = ?', array($isActive ? 1 : 0, $current['id']));
        json_ok(array('food' => get_admin_food($db, $current['id'])));
    }

    if ($method === 'DELETE' && preg_match('#^/foods/(\d+)$#', $sub, $m)) {
        $current = $db->fetch('SELECT * FROM foods WHERE id = ?', array($m[1]));
        if (!$current) {
            json_error(404, 'غذا پیدا نشد');
        }
        $db->exec('DELETE FROM food_sizes WHERE food_id = ?', array($current['id']));
        $db->exec('DELETE FROM foods WHERE id = ?', array($current['id']));
        delete_uploaded_file($current['image']);
        json_ok(array('ok' => true));
    }

    if ($method === 'PUT' && $sub === '/password') {
        $currentPassword = (string) input('currentPassword', '');
        $newPassword = (string) input('newPassword', '');
        if ($currentPassword === '' || $newPassword === '') {
            json_error(400, 'رمز فعلی و رمز جدید را وارد کنید');
        }
        if (strlen($newPassword) < 6) {
            json_error(400, 'رمز جدید باید حداقل ۶ کاراکتر باشد');
        }
        $row = $db->fetch('SELECT * FROM admins WHERE id = ?', array($admin['id']));
        if (!password_verify($currentPassword, $row['password_hash'])) {
            json_error(400, 'رمز فعلی اشتباه است');
        }
        $db->exec('UPDATE admins SET password_hash = ? WHERE id = ?', array(password_hash($newPassword, PASSWORD_DEFAULT), $admin['id']));
        json_ok(array('ok' => true));
    }

    if ($method === 'GET' && $sub === '/settings') {
        json_ok(array('settings' => get_settings_map($db)));
    }

    if ($method === 'PUT' && $sub === '/settings') {
        $incoming = input('settings');
        if (!is_array($incoming)) {
            $incoming = request_body();
        }
        if (!is_array($incoming)) {
            json_error(400, 'اطلاعات تنظیمات نامعتبر است');
        }
        $allowed = array(
            'site_name', 'site_title', 'footer_tagline', 'phone', 'address', 'hours',
            'hours_saturday', 'hours_sunday', 'hours_monday', 'hours_tuesday',
            'hours_wednesday', 'hours_thursday', 'hours_friday', 'instagram_url',
            'hero_tagline', 'hero_title_1', 'hero_title_2', 'hero_subtitle',
            'menu_tagline', 'menu_title', 'menu_subtitle',
            'about_title', 'about_text',
            'feature_1_title', 'feature_1_text',
            'feature_2_title', 'feature_2_text',
            'feature_3_title', 'feature_3_text',
            'home_specials_title', 'home_specials_subtitle'
        );
        $entries = array();
        foreach ($allowed as $key) {
            if (array_key_exists($key, $incoming)) {
                $entries[] = array('key' => $key, 'value' => trim_str($incoming[$key], 500));
            }
        }
        if (!$entries) {
            json_error(400, 'هیچ تنظیماتی ارسال نشده است');
        }
        upsert_settings($db, $entries);
        json_ok(array('settings' => get_settings_map($db)));
    }

    json_error(404, 'مسیر مورد نظر پیدا نشد');
}
