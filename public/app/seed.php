<?php

function default_settings()
{
    return array(
        'site_name' => 'MAHA',
        'site_title' => 'MAHA Fastfood',
        'footer_tagline' => 'BURGER & PIZZA',
        'phone' => '۹۳۱۱۲۲۸۲ - ۰۲۱',
        'address' => 'بومهن، بلوار چمران، میدان چمران، جنب پاساژ الماس',
        'hours' => '۱۱:۰۰ - ۲۲:۰۰',
        'hours_saturday' => '۱۱:۰۰ - ۲۲:۰۰',
        'hours_sunday' => '۱۱:۰۰ - ۲۲:۰۰',
        'hours_monday' => '۱۱:۰۰ - ۲۲:۰۰',
        'hours_tuesday' => '۱۱:۰۰ - ۲۲:۰۰',
        'hours_wednesday' => '۱۱:۰۰ - ۲۲:۰۰',
        'hours_thursday' => '۱۱:۰۰ - ۲۲:۰۰',
        'hours_friday' => '۱۱:۰۰ - ۲۲:۰۰',
        'instagram_url' => '',
        'hero_tagline' => 'MAHA FASTFOOD',
        'hero_title_1' => 'طعم واقعی،',
        'hero_title_2' => 'انتخاب خاص',
        'hero_subtitle' => 'بهترین غذاها با مواد اولیه تازه و کیفیتی که تجربه‌ای متفاوت می‌سازد.',
        'menu_tagline' => 'MAHA FASTFOOD',
        'menu_title' => 'منوی ماها',
        'menu_subtitle' => 'انتخاب کن، سفارش بده و از طعمش لذت ببر.',
        'about_title' => 'درباره ما',
        'about_text' => 'ما در MAHA تلاش می‌کنیم بهترین تجربه فست‌فود را با ترکیب کیفیت، طعم و خلاقیت برای شما فراهم کنیم.',
        'feature_1_title' => 'مواد تازه',
        'feature_1_text' => 'استفاده از بهترین مواد اولیه',
        'feature_2_title' => 'کیفیت بالا',
        'feature_2_text' => 'تهیه غذا با استاندارد حرفه‌ای',
        'feature_3_title' => 'سفارش سریع',
        'feature_3_text' => 'آماده‌سازی سریع و آسان',
        'home_specials_title' => 'پیشنهادهای ویژه',
        'home_specials_subtitle' => 'چند مورد از محبوب‌ترین غذاهای ما'
    );
}

function seed_missing_settings(Database $db)
{
    foreach (default_settings() as $key => $value) {
        $exists = $db->fetch('SELECT setting_key FROM settings WHERE setting_key = ?', array($key));
        if (!$exists) {
            $db->exec('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)', array($key, $value));
        }
    }
}

function seed_database(Database $db, $config)
{
    $adminCount = $db->fetch('SELECT COUNT(*) AS c FROM admins');
    if ((int) $adminCount['c'] === 0) {
        $db->exec(
            'INSERT INTO admins (username, password_hash) VALUES (?, ?)',
            array($config['admin_username'], password_hash($config['admin_password'], PASSWORD_DEFAULT))
        );
    }

    seed_missing_settings($db);

    $categoryCount = $db->fetch('SELECT COUNT(*) AS c FROM categories');
    if ((int) $categoryCount['c'] > 0) {
        return;
    }

    $categories = array(
        array('پیتزا', '🍕', 1, 1),
        array('پیش غذا', '🍟', 0, 2),
        array('ساندویچ', '🥪', 0, 3),
        array('برگر', '🍔', 0, 4),
        array('پاستا', '🍝', 0, 5)
    );

    $foods = array(
        array(
            'name' => 'پیتزا سیر و استیک',
            'category' => 'پیتزا',
            'description' => 'پیتزای ویژه با گوشت استیک، سیر و پنیر کشدار',
            'image' => '/images/steak-pizza.jpg',
            'sizes' => array(array('مینی', 330000), array('یک نفره', 650000), array('دو نفره', 950000), array('خانواده', 1200000))
        ),
        array(
            'name' => 'پیتزا مرغ و قارچ',
            'category' => 'پیتزا',
            'description' => 'ترکیب مرغ مزه‌دار شده با قارچ تازه و پنیر',
            'image' => '/images/chicken-pizza.jpg',
            'sizes' => array(array('مینی', 260000), array('یک نفره', 500000), array('دو نفره', 750000), array('خانواده', 980000))
        ),
        array(
            'name' => 'پیتزا پپرونی',
            'category' => 'پیتزا',
            'description' => 'پیتزا با پپرونی و پنیر مخصوص',
            'image' => '/images/pepperoni.jpg',
            'sizes' => array(array('مینی', 260000), array('یک نفره', 500000), array('دو نفره', 750000), array('خانواده', 980000))
        ),
        array(
            'name' => 'پیتزا مخلوط',
            'category' => 'پیتزا',
            'description' => 'ترکیبی از گوشت، مرغ، قارچ و مواد تازه',
            'image' => '/images/mix-pizza.jpg',
            'sizes' => array(array('مینی', 230000), array('یک نفره', 450000), array('دو نفره', 680000), array('خانواده', 900000))
        )
    );

    $categoryIds = array();
    foreach ($categories as $category) {
        $id = $db->exec(
            'INSERT INTO categories (name, icon, has_sizes, sort_order, is_active) VALUES (?, ?, ?, ?, 1)',
            $category
        );
        $categoryIds[$category[0]] = $id;
    }

    foreach ($foods as $index => $food) {
        $foodId = $db->exec(
            'INSERT INTO foods (name, category_id, description, image, price, is_active, sort_order) VALUES (?, ?, ?, ?, ?, 1, ?)',
            array($food['name'], $categoryIds[$food['category']], $food['description'], $food['image'], null, $index + 1)
        );
        foreach ($food['sizes'] as $sizeIndex => $size) {
            $db->exec(
                'INSERT INTO food_sizes (food_id, label, price, sort_order) VALUES (?, ?, ?, ?)',
                array($foodId, $size[0], $size[1], $sizeIndex + 1)
            );
        }
    }
}
