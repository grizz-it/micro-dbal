<?php

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use GrizzIt\MicroDbal\Migration\MigrationInterface;

class CreateMigrationsTable implements MigrationInterface
{
    /**
     * Run the database migration.
     *
     * @param Schema $schema
     *
     * @return void
     *
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('migrations');
        $table->addColumn('migration', 'string');
    }

    /**
     * Revert the database migration.
     *
     * @param Schema $schema
     *
     * @return void
     *
     * @throws SchemaException
     */
    public function down(Schema $schema): void
    {
        $schema->dropTable('migrations');
    }
}