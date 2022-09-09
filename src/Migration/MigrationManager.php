<?php

namespace GrizzIt\MicroDbal\Migration;

use GrizzIt\MicroDbal\Connection\ConnectionManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DoctrineException;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Exception;

class MigrationManager
{
    private static array $migrations = [];

    /** @var AbstractSchemaManager[] */
    private static array $connectionToSchema = [];

    private static array $defaultMigrations = [];

    /** @var MigrationInterface[] */
    private static array $migrationInstances = [];

    private static array $migrationsRun = [];

    private static array $migrationsDirectories = [
        __DIR__ . '/../../migrations'
    ];

    /**
     * Run all migrations for all connections.
     *
     * @return void
     *
     * @throws DoctrineException
     */
    public static function runMigrations(): void
    {
        echo "Collecting migrations to run...\n";
        foreach (static::collectMigrations() as $config) {
            $connectionName = $config['connection'];
            $manager = static::getSchemaManagerForConnection($connectionName);
            static::runDefaultMigrations($connectionName, $manager);
            $connection = ConnectionManager::getConnection($connectionName);
            $fileName = basename($config['path']);
            if (!static::hasMigrationRun($connection, $fileName, $connectionName)) {
                static::runMigration($config, $manager);
            }
        }

        echo "Done!\n";
    }

    /**
     * Set the directory that contains all migrations.
     *
     * @param string $directory
     *
     * @return void
     */
    public static function addMigrationsDirectory(string $directory): void
    {
        static::$migrationsDirectories[] = $directory;
    }

    /**
     * Revert a migration by its filename.
     *
     * @param string $migration
     *
     * @return void
     *
     * @throws DoctrineException
     */
    public static function revertMigration(string $migration): void
    {
        echo "Collecting migrations...\n";
        $migrations = static::collectMigrations();
        if (!isset($migrations[$migration])) {
            throw new Exception(sprintf(
                'Can not revert migrations %s, it does not exist!',
                $migration
            ));
        }

        $connectionName = $migrations[$migration]['connection'];
        $manager = static::getSchemaManagerForConnection($connectionName);
        static::runDefaultMigrations($connectionName, $manager);
        $connection = ConnectionManager::getConnection($connectionName);
        $fileName = basename($migrations[$migration]['path']);
        if (static::hasMigrationRun($connection, $fileName, $connectionName)) {
            static::runMigration($migrations[$migration], $manager, true);
        } else {
            echo "Migration was not executed\n";
        }

        echo "Done!\n";
    }

    /**
     * Check if a migration has run.
     *
     * @param Connection $connection
     * @param string $migration
     * @param string $connectionName
     *
     * @return bool
     *
     * @throws DoctrineException
     */
    private static function hasMigrationRun(
        Connection $connection,
        string $migration,
        string $connectionName
    ): bool {
        if (empty(static::$migrationsRun[$connectionName])) {
            static::$migrationsRun[$connectionName] = array_column(
                $connection->createQueryBuilder()
                    ->select('*')
                    ->from('migrations')
                    ->executeQuery()
                    ->fetchAllAssociative(),
                'migration'
            );
        }

        return in_array($migration, static::$migrationsRun[$connectionName]);
    }

    /**
     * Run the default migrations.
     *
     * @param string $connection
     * @param AbstractSchemaManager $schemaManager
     *
     * @return void
     *
     * @throws DoctrineException
     */
    private static function runDefaultMigrations(
        string $connection,
        AbstractSchemaManager $schemaManager
    ): void {
        if (!$schemaManager->tablesExist(['migrations'])) {
            foreach (static::$defaultMigrations as $migration) {
                $migration['connection'] = $connection;
                static::runMigration($migration, $schemaManager);
            }
        }
    }

    /**
     * Run a migration by its configuration.
     *
     * @param array $migration
     * @param AbstractSchemaManager $schemaManager
     * @param bool $revert
     *
     * @return void
     *
     * @throws DoctrineException
     */
    private static function runMigration(
        array $migration,
        AbstractSchemaManager $schemaManager,
        bool $revert = false
    ): void {
        $fileName = basename($migration['path']);
        echo "\n" . sprintf(
            '%s migration %s for %s',
            ($revert ? 'Reverting' : 'Running') ,
            $fileName,
            $migration['connection']
        );

        $connection = ConnectionManager::getConnection($migration['connection']);
        $schema = $schemaManager->createSchema();
        $migration = static::getMigrationInstance($migration);
        if ($revert) {
            $migration->down($schema);
        } else {
            $migration->up($schema);
        }

        $schemaManager->migrateSchema($schema);

        if ($revert) {
            $connection->createQueryBuilder()
                ->delete('migrations')
                ->where('migration = :migration')
                ->setParameters(['migration' => $fileName])
                ->executeQuery();
        } else {
            $connection->createQueryBuilder()
                ->insert('migrations')
                ->values(['migration' => ':migration'])
                ->setParameters(['migration' => $fileName])
                ->executeQuery();
        }

        echo "\n";
    }

    /**
     * Create or retrieve a migration instance.
     *
     * @param array $migration
     *
     * @return MigrationInterface
     */
    private static function getMigrationInstance(array $migration): MigrationInterface
    {
        if (!isset(static::$migrationInstances[$migration['class']])) {
            require_once($migration['path']);
            static::$migrationInstances[$migration['class']] = new ($migration['class'])();
        }

        return static::$migrationInstances[$migration['class']];
    }

    /**
     * Create or retrieve a schema for a connection.
     *
     * @param string $connection
     *
     * @return AbstractSchemaManager
     *
     * @throws DoctrineException
     */
    private static function getSchemaManagerForConnection(string $connection): AbstractSchemaManager
    {
        if (!isset(static::$connectionToSchema[$connection])) {
            static::$connectionToSchema[$connection] = ConnectionManager::getConnection(
                $connection
            )->createSchemaManager();
        }

        return static::$connectionToSchema[$connection];
    }

    /**
     * Retrieve all migrations.
     *
     * @return array
     *
     * @throws Exception
     */
    private static function collectMigrations(): array
    {
        if (!empty(static::$migrations)) {
            return static::$migrations;
        }

        foreach(static::$migrationsDirectories as $migrationsPath) {
            foreach (static::scanDirectory($migrationsPath) as $connection) {
                if (is_dir($migrationsPath . $connection)) {
                    static::collectMigrationsForConnection(
                        $connection,
                        $migrationsPath . $connection . '/'
                    );
                }
            }
        }

        ksort(static::$migrations);
        ksort(static::$defaultMigrations);

        return static::$migrations;
    }

    /**
     * Collection migrations for connection by path.
     *
     * @param string $connection
     * @param string $path
     *
     * @return void
     */
    private static function collectMigrationsForConnection(
        string $connection,
        string $path
    ): void {
        foreach (static::scanDirectory($path) as $item) {
            if (is_dir($path . $item)) {
                static::collectMigrationsForConnection($connection, $path . $item . '/');
            }

            $class = array_reverse(explode('_', str_replace('.php', '', $item)));
            for($i = 0; $i < 6; $i++) {
                array_pop($class);
            }

            $class = implode('', array_map('ucfirst', array_reverse($class)));
            if ($connection === 'migrations') {
                static::$defaultMigrations[$item] = [
                    'connection' => $connection,
                    'path' => $path . $item,
                    'class' => $class,
                ];

                continue;
            }

            static::$migrations[$item] = [
                'connection' => $connection,
                'path' => $path . $item,
                'class' => $class,
            ];
        }
    }

    /**
     * Scans a directory without returning trash.
     *
     * @param string $directory
     *
     * @return array
     */
    private static function scanDirectory(string $directory): array
    {
        return array_diff(scandir($directory), array('..', '.'));
    }
}