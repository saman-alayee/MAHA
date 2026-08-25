<?php
require __DIR__ . '/../app/bootstrap.php';
require APP_DIR . '/routes.php';
dispatch($db, $config);
