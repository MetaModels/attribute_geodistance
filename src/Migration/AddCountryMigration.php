<?php

/**
 * This file is part of MetaModels/attribute_geodistance.
 *
 * (c) 2012-2024 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_geodistance
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_geodistance/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

declare(strict_types=1);

namespace MetaModels\AttributeGeoDistanceBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;

/**
 * Update the database table "tl_metamodel_attribute".
 * - Create the new column "countrymode".
 * - Change the column "get_land => country_get".
 * - If exists entries in the old field "get_land",
 *   then switch the "countrymode" to the option "get".
 */
final class AddCountryMigration extends AbstractMigration
{
    /**
     * The database connection.
     *
     * @var Connection
     */
    private Connection $connection;

    /**
     * Create a new instance.
     *
     * @param Connection $connection The database connection.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Return the name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Add countrymode and country_get in MetaModels attribute table if not exist.';
    }

    /**
     * Must only run if:
     * - the MM tables are present AND
     * - there are some columns defined
     *
     * @return bool
     */
    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_metamodel', 'tl_metamodel_attribute'])) {
            return false;
        }

        if (!$this->fieldExists('tl_metamodel_attribute', 'get_land')) {
            return false;
        }

        if (!$this->fieldExists('tl_metamodel_attribute', 'country_get')) {
            return true;
        }

        return false;
    }

    /**
     * Create the missing columns and copy existing values;
     * drop column get_land manually in install tool.
     *
     * @return MigrationResult
     */
    public function run(): MigrationResult
    {
        $this->alterTable();
        $this->updateColumnDefaultValue();
        $this->setCountryMode();

        return new MigrationResult(true, 'Adjusted table tl_metamodel_attribute with countrymode and country_get');
    }

    /**
     * Alter the table "tl_metamodel_attribute".
     *
     * @return void
     */
    private function alterTable(): void
    {
        $manager = $this->connection->createSchemaManager();
        $table   = $manager->introspectTable('tl_metamodel_attribute');

        /** @psalm-suppress InternalMethod - Class TableDiff is internal, but this just works fine. */
        $tableDiff            = new TableDiff('tl_metamodel_attribute');
        /** @psalm-suppress InternalProperty - ToDo: Duplicate Code? Constructor set the fromTable. */
        $tableDiff->fromTable = $table;

        $this->addColumnCountryMode($tableDiff);
        $this->changeColumnGetLand($tableDiff);

        $manager->alterTable($tableDiff);
    }

    /**
     * Add column "countrymode".
     *
     * @param TableDiff $tableDiff The table diff.
     *
     * @return void
     *
     * @throws Exception
     */
    private function addColumnCountryMode(TableDiff $tableDiff): void
    {
        $column = new Column('countrymode', Type::getType(Types::STRING));
        $column
            ->setLength(255)
            ->setNotnull(true)
            ->setDefault('');

        /** @psalm-suppress InternalProperty - We want to add some data but there is no set or add. */
        $tableDiff->addedColumns[] = $column;
    }

    /**
     * Change column "get_land => country_get".
     *
     * @param TableDiff $tableDiff The table diff.
     *
     * @return void
     *
     * @throws SchemaException
     * @throws Exception
     */
    private function changeColumnGetLand(TableDiff $tableDiff): void
    {
        $changeColumn = new Column('country_get', Type::getType(Types::TEXT));
        $changeColumn
            ->setLength(AbstractMySQLPlatform::LENGTH_LIMIT_TEXT)
            ->setNotnull(false)
            ->setDefault(null);
        /** @psalm-suppress InternalMethod - Class ColumnDiff is internal, but this just works fine. */
        $columnDiff = new ColumnDiff('get_land', $changeColumn);

        /** @psalm-suppress InternalProperty - We want to add some data but there is no set or add. */
        $tableDiff->changedColumns[] = $columnDiff;
    }

    /**
     * Update column "country_get" default value.
     *
     * @return void
     *
     * @throws Exception
     */
    private function updateColumnDefaultValue(): void
    {
        $this->connection->createQueryBuilder()
            ->update('tl_metamodel_attribute', 't')
            ->set('t.country_get', 'null')
            ->where('t.country_get = ""')
            ->executeStatement();
    }

    /**
     * Set the country mode to get, if the country get is not empty.
     *
     * @return void
     */
    private function setCountryMode(): void
    {
        $this->connection->createQueryBuilder()
            ->update('tl_metamodel_attribute', 't')
            ->set('t.countrymode', '"get"')
            ->where('t.country_get != ""')
            ->executeQuery();
    }

    /**
     * Check is a table column exists.
     *
     * @param string $tableName  Table name.
     * @param string $columnName Column name.
     *
     * @return bool
     */
    private function fieldExists(string $tableName, string $columnName): bool
    {
        /** @var Column[] $columns */
        $columns = [];
        // The schema manager return the column list with lowercase keys, wo got to use the real names.
        \array_map(
            static function (Column $column) use (&$columns) {
                $columns[$column->getName()] = $column;
            },
            $this->connection->createSchemaManager()->listTableColumns($tableName)
        );

        return isset($columns[$columnName]);
    }
}
