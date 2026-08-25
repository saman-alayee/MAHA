<?php

function food_select_sql($where = '', $extra = '')
{
    return "
        SELECT foods.*, categories.name AS category_name, categories.has_sizes
        FROM foods
        JOIN categories ON categories.id = foods.category_id
        $where
        $extra
    ";
}

function get_sizes(Database $db, $foodId)
{
    $rows = $db->fetchAll(
        'SELECT id, label, price, sort_order AS sortOrder FROM food_sizes WHERE food_id = ? ORDER BY sort_order, id',
        array($foodId)
    );
    foreach ($rows as &$row) {
        $row['id'] = (int) $row['id'];
        $row['price'] = (int) $row['price'];
        $row['sortOrder'] = (int) $row['sortOrder'];
    }
    return $rows;
}

function map_food(Database $db, $row)
{
    $sizes = get_sizes($db, $row['id']);
    $sizePairs = array();
    foreach ($sizes as $size) {
        $sizePairs[] = array($size['label'], (int) $size['price']);
    }
    return array(
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'category' => $row['category_name'],
        'categoryId' => (int) $row['category_id'],
        'description' => $row['description'],
        'image' => $row['image'],
        'price' => $row['price'] === null ? null : (int) $row['price'],
        'sizes' => $sizePairs ? $sizePairs : null,
        'sizeList' => $sizes,
        'isActive' => bool_json($row['is_active']),
        'sortOrder' => (int) $row['sort_order'],
        'hasSizes' => bool_json($row['has_sizes'])
    );
}

function get_settings_map(Database $db)
{
    $rows = $db->fetchAll('SELECT setting_key, setting_value FROM settings');
    $map = array();
    foreach ($rows as $row) {
        $map[$row['setting_key']] = $row['setting_value'];
    }
    return $map;
}

function upsert_settings(Database $db, $entries)
{
    foreach ($entries as $entry) {
        $exists = $db->fetch('SELECT setting_key FROM settings WHERE setting_key = ?', array($entry['key']));
        if ($exists) {
            $db->exec('UPDATE settings SET setting_value = ? WHERE setting_key = ?', array($entry['value'], $entry['key']));
        } else {
            $db->exec('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)', array($entry['key'], $entry['value']));
        }
    }
}

function save_food_sizes(Database $db, $foodId, $sizes)
{
    $db->exec('DELETE FROM food_sizes WHERE food_id = ?', array($foodId));
    foreach ($sizes as $index => $size) {
        $db->exec(
            'INSERT INTO food_sizes (food_id, label, price, sort_order) VALUES (?, ?, ?, ?)',
            array($foodId, $size['label'], $size['price'], isset($size['sortOrder']) ? $size['sortOrder'] : $index + 1)
        );
    }
}

function get_admin_food(Database $db, $id)
{
    $row = $db->fetch(food_select_sql('WHERE foods.id = ?'), array($id));
    if (!$row) {
        json_error(404, 'غذا پیدا نشد');
    }
    return map_food($db, $row);
}

function map_category_row($row, $withActive = false)
{
    $item = array(
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'icon' => $row['icon'],
        'hasSizes' => bool_json($row['hasSizes']),
        'sortOrder' => (int) $row['sortOrder']
    );
    if ($withActive) {
        $item['isActive'] = bool_json($row['isActive']);
    }
    return $item;
}
