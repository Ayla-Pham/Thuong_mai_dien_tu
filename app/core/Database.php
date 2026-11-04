<?php
/**
 * Database: kết nối MySQL bằng PDO (Singleton - chỉ tạo 1 kết nối duy nhất).
 * Luôn dùng prepared statements khi truy vấn để chống SQL Injection.
 */
class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            global $config;
            $c = $config['db'];
            $dsn = "mysql:host={$c['host']};dbname={$c['name']};charset={$c['charset']}";
            self::$instance = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // lỗi SQL ném exception
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // kết quả dạng mảng kết hợp
                PDO::ATTR_EMULATE_PREPARES   => false,                   // dùng prepared statement thật
            ]);
        }
        return self::$instance;
    }
}
