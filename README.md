# GrizzIT - Micro DBAL

A minimalist DBAL implementation ideal for projects requiring a small
implementation of database management.

## Adding a migration directory

In order to add a directory that contains migrations, add the following line:

```php
<?php

use GrizzIt\MicroDbal\Migration\MigrationManager;
MigrationManager::addMigrationsDirectory('directory/to/migrations');
```

## Adding a database connection

In order to create a new database connection, add the following line:
```php
<?php

use GrizzIt\MicroDbal\Connection\ConnectionManager;

ConnectionManager::addConnection(
    'master',
    [
        'dbname' => $_ENV['DB_DATABASE'],
        'user' => $_ENV['DB_USERNAME'],
        'password' => $_ENV['DB_PASSWORD'],
        'host' => $_ENV['DB_HOST'],
        'port' => $_ENV['DB_PORT'],
        'driver' => 'pdo_mysql',
    ]
);
```

The first parameter in this call determines the key of the connection.

## Retrieving a connection by key

To retrieve a connection by its key, use the following snippet:

```php
<?php

use GrizzIt\MicroDbal\Connection\ConnectionManager;

ConnectionManager::getConnection('master');
```

## Creating migrations

In the `migrations` directory, create a folder with the same name as the
configured connection required for the migration. Then add the migrations
anywhere inside that folder. Let the migration implement the interface
`GrizzIt\MicroDbal\Migration\MigrationInterface`.

The name of the file should always contain the date prefix:
`YYYY_MM_DD_HH_mm_SS_`. This part will be used for sorting the migrations.

After that the name of class inside the file must be defined as lowercase and
separated by underscores for words, e.g.: `CreateExampleTable` should be:
`YYYY_MM_DD_HH_mm_SS_create_example_table.php`. All migration names MUST be
unique.

### Default migrations
The `migrations` subdirectory is a reserved for running migrations that should
be run for every connection.

### Running migrations
Migrations can be run with the following snippet:
```php
<?php

use GrizzIt\MicroDbal\Migration\MigrationManager;

MigrationManager::runMigrations();
```
This will run all migrations (that have not been run) for all connections.

### Reverting migrations
To revert a migration, use the following snippet:
```php
<?php

use GrizzIt\MicroDbal\Migration\MigrationManager;

MigrationManager::revertMigration('2022_01_01_10_00_00_create_migrations_table.php');
```

## MIT License

Copyright (c) GrizzIT

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.