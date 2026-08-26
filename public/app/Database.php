<?php

class Database
{
    /** @var PDO */
    public $pdo;
    public $driver;

    public function __construct(PDO $pdo, $driver)
    {
        $this->pdo = $pdo;
        $this->driver = $driver;
    }

    public static function connect($config)
    {
        $driver = $config['db_connection'];
        if ($driver === 'mysql') {
            $dsn = 'mysql:host=' . $config['db_host'] . ';port=' . $config['db_port'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4';
            $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
            ));
        } else {
            $path = $config['db_path'];
            if ($path !== '' && $path[0] !== '/' && !preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
                $path = ROOT_DIR . '/' . $path;
            }
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $pdo = new PDO('sqlite:' . $path, null, null, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ));
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA journal_mode = WAL');
        }
        return new self($pdo, $driver);
    }

    public function q($sql, $params = array())
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($params));
        return $stmt;
    }

    public function fetch($sql, $params = array())
    {
        return $this->q($sql, $params)->fetch();
    }

    public function fetchAll($sql, $params = array())
    {
        return $this->q($sql, $params)->fetchAll();
    }

    public function exec($sql, $params = array())
    {
        $this->q($sql, $params);
        return (int) $this->pdo->lastInsertId();
    }

    public function nowFn()
    {
        return $this->driver === 'mysql' ? 'NOW()' : "datetime('now')";
    }
}

function migrate(Database $db, $driver)
{
    if ($driver === 'mysql') {
        $queries = array(
            "CREATE TABLE IF NOT EXISTS admins (
              id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              username VARCHAR(80) NOT NULL UNIQUE,
              password_hash VARCHAR(255) NOT NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS categories (
              id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              name VARCHAR(80) NOT NULL UNIQUE,
              icon VARCHAR(16) NOT NULL DEFAULT '',
              has_sizes TINYINT(1) NOT NULL DEFAULT 0,
              sort_order INT NOT NULL DEFAULT 0,
              is_active TINYINT(1) NOT NULL DEFAULT 1,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS foods (
              id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              name VARCHAR(120) NOT NULL,
              category_id INT UNSIGNED NOT NULL,
              description TEXT NOT NULL,
              image VARCHAR(500) NOT NULL DEFAULT '',
              price INT NULL,
              is_active TINYINT(1) NOT NULL DEFAULT 1,
              sort_order INT NOT NULL DEFAULT 0,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS food_sizes (
              id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              food_id INT UNSIGNED NOT NULL,
              label VARCHAR(40) NOT NULL,
              price INT NOT NULL DEFAULT 0,
              sort_order INT NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS settings (
              setting_key VARCHAR(80) PRIMARY KEY,
              setting_value TEXT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        foreach ($queries as $sql) {
            $db->pdo->exec($sql);
        }
        return;
    }
        $db->pdo->exec("
            CREATE TABLE IF NOT EXISTS admins (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              username TEXT NOT NULL UNIQUE,
              password_hash TEXT NOT NULL,
              created_at TEXT NOT NULL DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS categories (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              name TEXT NOT NULL UNIQUE,
              icon TEXT NOT NULL DEFAULT '',
              has_sizes INTEGER NOT NULL DEFAULT 0,
              sort_order INTEGER NOT NULL DEFAULT 0,
              is_active INTEGER NOT NULL DEFAULT 1,
              created_at TEXT NOT NULL DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS foods (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              name TEXT NOT NULL,
              category_id INTEGER NOT NULL,
              description TEXT NOT NULL DEFAULT '',
              image TEXT NOT NULL DEFAULT '',
              price INTEGER,
              is_active INTEGER NOT NULL DEFAULT 1,
              sort_order INTEGER NOT NULL DEFAULT 0,
              created_at TEXT NOT NULL DEFAULT (datetime('now')),
              updated_at TEXT NOT NULL DEFAULT (datetime('now')),
              FOREIGN KEY (category_id) REFERENCES categories(id)
            );
            CREATE TABLE IF NOT EXISTS food_sizes (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              food_id INTEGER NOT NULL,
              label TEXT NOT NULL,
              price INTEGER NOT NULL DEFAULT 0,
              sort_order INTEGER NOT NULL DEFAULT 0,
              FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
            );
            CREATE TABLE IF NOT EXISTS settings (
              setting_key TEXT PRIMARY KEY,
              setting_value TEXT NOT NULL DEFAULT ''
            );
        ");
}
