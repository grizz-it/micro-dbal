<?php

namespace GrizzIt\MicroDbal\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DoctrineException;
use Exception;

class ConnectionManager
{
    private static array $connectionConfiguration = [];

    private static array $connections = [];

    /**
     * Add a connection configuration.
     *
     * @param string $key
     * @param array $params
     *
     * @return void
     */
    public static function addConnection(string $key, array $params): void
    {
        static::$connectionConfiguration[$key] = $params;
    }

    /**
     * Retrieve a database connection.
     *
     * @param string $key
     *
     * @return Connection
     *
     * @throws DoctrineException
     */
    public static function getConnection(string $key): Connection
    {
        if (!isset(static::$connections[$key])) {
            if (!isset(static::$connectionConfiguration[$key])) {
                throw new Exception(
                    sprintf('Connection with key %s does not exist!', $key)
                );
            }

            static::$connections[$key] = DriverManager::getConnection(
                static::$connectionConfiguration[$key]
            );
        }

        return static::$connections[$key];
    }
}