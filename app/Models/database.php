<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo;
    if (!$pdo) {
        $path = dirname(__DIR__, 2) . '/config/database.local.php';
        if (!is_file($path)) {
            throw new RuntimeException('Salin konfigurasi database sesuai README.');
        }
        $config = require $path;
        $pdo = new PDO($config['dsn'], $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("SET time_zone = '+07:00'");
    }
    return $pdo;
}

function query(string $sql, array $params = []): PDOStatement
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    return $statement;
}

function rows(string $sql, array $params = []): array
{
    return query($sql, $params)->fetchAll();
}

function row(string $sql, array $params = []): array|false
{
    return query($sql, $params)->fetch();
}

function atomic(callable $execute): mixed
{
    db()->beginTransaction();
    try {
        $result = $execute();
        db()->commit();
        return $result;
    } catch (Throwable $error) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $error;
    }
}
