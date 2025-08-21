<?php
namespace HeyHi;

use PDO;

class Db {
    public static function connect(): PDO {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $_ENV['DB_HOST'],
            $_ENV['DB_PORT'],
            $_ENV['DB_NAME']
        );
        return new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);
    }
}