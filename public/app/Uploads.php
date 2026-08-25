<?php

function handle_food_upload($config)
{
    if (empty($_FILES['image']) || !isset($_FILES['image']['tmp_name']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    $file = $_FILES['image'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        json_error(400, 'آپلود تصویر نامعتبر است');
    }
    if ($file['size'] > $config['max_file_size']) {
        json_error(400, 'حجم تصویر نباید بیشتر از ۵ مگابایت باشد');
    }
    $info = @getimagesize($file['tmp_name']);
    if (!$info || empty($info['mime'])) {
        json_error(400, 'فقط تصویر JPG، PNG، WEBP یا GIF مجاز است');
    }
    $map = array(
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    );
    if (!isset($map[$info['mime']])) {
        json_error(400, 'فقط تصویر JPG، PNG، WEBP یا GIF مجاز است');
    }
    $name = time() . '-' . bin2hex(random_bytes(8)) . '.' . $map[$info['mime']];
    $dest = UPLOAD_DIR . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        json_error(400, 'ذخیره تصویر انجام نشد');
    }
    optimize_image($dest, $info['mime']);
    $jpg = preg_replace('/\.[^.]+$/', '.jpg', $dest);
    if ($info['mime'] !== 'image/gif' && is_file($jpg)) {
        $name = basename($jpg);
    }
    return '/uploads/foods/' . $name;
}

function optimize_image($path, $mime)
{
    if ($mime === 'image/gif' || !function_exists('imagecreatefromjpeg')) {
        return;
    }
    $src = null;
    if ($mime === 'image/jpeg') {
        $src = @imagecreatefromjpeg($path);
    } elseif ($mime === 'image/png') {
        $src = @imagecreatefrompng($path);
    } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
        $src = @imagecreatefromwebp($path);
    }
    if (!$src) {
        return;
    }
    $w = imagesx($src);
    $h = imagesy($src);
    $max = 1600;
    if ($w > $max || $h > $max) {
        $scale = min($max / $w, $max / $h);
        $nw = (int) round($w * $scale);
        $nh = (int) round($h * $scale);
        $dst = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);
        $src = $dst;
    }
    $jpg = preg_replace('/\.[^.]+$/', '.jpg', $path);
    imagejpeg($src, $jpg, 82);
    imagedestroy($src);
    if ($jpg !== $path && is_file($path)) {
        unlink($path);
    }
}

function delete_uploaded_file($imagePath)
{
    if (!$imagePath || strpos($imagePath, '/uploads/foods/') !== 0) {
        return;
    }
    $full = UPLOAD_DIR . '/' . basename($imagePath);
    if (is_file($full)) {
        unlink($full);
    }
}
