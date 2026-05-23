<?php
// config/db.php

class DB
{
    private static ?PDO $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $credsFile = __DIR__ . '/credentials.php';
        if (file_exists($credsFile)) {
            require_once $credsFile;
            $host = CRED_DB_HOST;
            $name = CRED_DB_NAME;
            $user = CRED_DB_USER;
            $pass = CRED_DB_PASSWORD;
            $port = '3306';
        } else {
            $host = $_ENV['DB_HOST']     ?? 'localhost';
            $name = $_ENV['DB_NAME']     ?? 'homeplate';
            $user = $_ENV['DB_USER']     ?? 'root';
            $pass = $_ENV['DB_PASSWORD'] ?? '';
            $port = $_ENV['DB_PORT']     ?? '3306';
        }

        $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            self::$pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Never expose DB credentials in production
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed']));
        }

        return self::$pdo;
    }

    /** Shorthand: execute a prepared statement and return the statement */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function lastInsertId(): string
    {
        return self::connect()->lastInsertId();
    }
}
