<?php
/**
 * Vishal Web Studio - Database Connection & Driver
 * Supports MySQL (Default for XAMPP/Production) and SQLite (Zero-config fallback)
 */

require_once __DIR__ . '/app.php';

class Database {
    private static ?Database $instance = null;
    private ?PDO $pdo = null;
    private string $driver = 'mysql';

    private function __construct() {
        $dbType = getenv('DB_TYPE') ?: 'auto';
        $mysqlHost = getenv('DB_HOST') ?: 'localhost';
        $mysqlPort = getenv('DB_PORT') ?: '3306';
        $mysqlDb   = getenv('DB_NAME') ?: 'vishal_web_studio';
        $mysqlUser = getenv('DB_USER') ?: 'root';
        $mysqlPass = getenv('DB_PASS') ?: '';

        $sqliteFile = DATABASE_PATH . DIRECTORY_SEPARATOR . 'vishal_web_studio.sqlite';

        // Check if MySQL connection works, else auto-fallback to SQLite
        if ($dbType === 'mysql' || $dbType === 'auto') {
            try {
                $dsn = "mysql:host={$mysqlHost};port={$mysqlPort};dbname={$mysqlDb};charset=utf8mb4";
                $this->pdo = new PDO($dsn, $mysqlUser, $mysqlPass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_TIMEOUT            => 2,
                ]);
                $this->driver = 'mysql';
                return;
            } catch (PDOException $e) {
                if ($dbType === 'mysql') {
                    // Try to connect to MySQL server without dbname to create the database if missing
                    try {
                        $dsnRoot = "mysql:host={$mysqlHost};port={$mysqlPort};charset=utf8mb4";
                        $pdoRoot = new PDO($dsnRoot, $mysqlUser, $mysqlPass, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                        ]);
                        $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `{$mysqlDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        
                        // Reconnect with dbname
                        $this->pdo = new PDO($dsn, $mysqlUser, $mysqlPass, [
                            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES   => false,
                        ]);
                        $this->driver = 'mysql';
                        return;
                    } catch (PDOException $ex) {
                        // MySQL truly unavailable
                    }
                }
            }
        }

        // SQLite Fallback
        if (!is_dir(DATABASE_PATH)) {
            mkdir(DATABASE_PATH, 0755, true);
        }
        $this->pdo = new PDO("sqlite:" . $sqliteFile, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->pdo->exec("PRAGMA foreign_keys = ON;");
        $this->driver = 'sqlite';
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->pdo;
    }

    public function getDriver(): string {
        return $this->driver;
    }

    public function isMySQL(): bool {
        return $this->driver === 'mysql';
    }
}

function db(): PDO {
    return Database::getInstance()->getConnection();
}
