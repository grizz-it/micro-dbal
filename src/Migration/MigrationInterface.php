<?php

namespace GrizzIt\MicroDbal\Migration;

use Doctrine\DBAL\Schema\Schema;

interface MigrationInterface
{
    /**
     * Run the database migration.
     *
     * @param Schema $schema
     *
     * @return void
     */
    public function up(Schema $schema): void;

    /**
     * Revert the database migration.
     *
     * @param Schema $schema
     *
     * @return void
     */
    public function down(Schema $schema): void;
}