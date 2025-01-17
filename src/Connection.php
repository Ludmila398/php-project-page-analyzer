<?php

namespace App;

final class Connection
{
    private static ?Connection $conn = null;

    public function connect()
    {
        if (getenv('DATABASE_URL')) {
            $databaseUrl = parse_url(getenv('DATABASE_URL'));
        }

        if (isset($databaseUrl['host'])) {
            $params['host'] = $databaseUrl['host'];
            $params['port'] = isset($databaseUrl['port']) ? $databaseUrl['port'] : 5432;
            $params['database'] = isset($databaseUrl['path']) ? ltrim($databaseUrl['path'], '/') : null;
            $params['user'] = isset($databaseUrl['user']) ? $databaseUrl['user'] : null;
            $params['passw'] = isset($databaseUrl['pass']) ? $databaseUrl['pass'] : null;
        } else {
            $params = parse_ini_file('database.ini');
        }
        if ($params === false) {
            throw new \Exception("Error reading database configuration file");
        }

        $connectionSettings = sprintf(
            "pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
            $params['host'],
            $params['port'],
            $params['database'],
            $params['user'],
            $params['passw']
        );

        $pdo = new \PDO($connectionSettings);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    public static function get(): self
    {
        if (null === static::$conn) {
            static::$conn = new self();
        }

        return static::$conn;
    }
}
