<?php declare(strict_types=1);

namespace Cocoders\Dbal\EventStore;

use Cocoders\EventStore\EventStream;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;

final class SchemaManager
{
    private $connection;
    private $registeredStreams = [];

    public static function normalizeToTableName(EventStream\Name $name): string
    {
        $name = (string) $name;
        $name = str_replace(['\\', '-', ':', '.'], '_', $name);

        return $name;
    }

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function createStreamTable(EventStream\Name $name)
    {
        $this->registeredStreams[(string) $name] = $name;
        $tableName = static::normalizeToTableName($name);

        $table = $this->createTable($tableName);

        $dbalSchemaManager = $this->connection->getSchemaManager();
        $dbalSchemaManager->createTable($table);
    }

    public function dropStreamTable(EventStream\Name $name)
    {
        $tableName = static::normalizeToTableName($name);

        $dbalSchemaManager = $this->connection->getSchemaManager();
        try {
            $dbalSchemaManager->dropTable($tableName);
        } catch (\PDOException $e) {
        }
    }

    private function createTable(string $tableName): Table
    {
        $table = new Table($tableName);
        $table->addColumn(
            'aggregate_id',
            'guid',
            [
                'notnull' => true
            ]
        );
        $table->addColumn(
            'occurred_on',
            'datetime',
            [
                'notnull' => true
            ]
        );
        $table->addColumn(
            'name',
            'string',
            [
                'notnull' => true
            ]
        );
        $table->addColumn(
            'event_class',
            'string',
            [
                'notnull' => true
            ]
        );
        $table->addColumn(
            'event',
            'text'
        );
        $table->addIndex(['aggregate_id']);
        $table->addIndex(['occurred_on']);

        return $table;
    }
}

