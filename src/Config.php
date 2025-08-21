<?php
namespace HeyHi;

use Dotenv\Dotenv;

class Config {
    public static function load(): void {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();
    }
}