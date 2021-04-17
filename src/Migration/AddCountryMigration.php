<?php

/**
 * This file is part of MetaModels/attribute_geodistance.
 *
 * (c) 2012-2021 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_geodistance
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2021 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_geodistance/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

declare(strict_types=1);

namespace MetaModels\AttributeGeoDistanceBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use MetaModels\Helper\TableManipulator;

/**
 * Update the database table "tl_metamodel_attribute".
 * - Create the new column "countrymode".
 * - Create the new column "country_get".
 * - If exists entries in the old field "get_land",
 *   then switch the "countrymode" to the option "get"
 *   and store the data from the old field to the new field "country_get".
 */
class AddCountryMigration extends AbstractMigration
{
    /**
     * The database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * Table manipulator.
     *
     * @var TableManipulator
     */
    protected $tableManipulator;

    /**
     * Create a new instance.
     *
     * @param Connection       $connection       The database connection.
     * @param TableManipulator $tableManipulator The table manipulator.
     */
    public function __construct(Connection $connection, TableManipulator $tableManipulator)
    {
        $this->connection       = $connection;
        $this->tableManipulator = $tableManipulator;
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
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_metamodel', 'tl_metamodel_attribute'])) {
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
        if (!$this->fieldExists('tl_metamodel_attribute', 'country_get')) {
            $this->tableManipulator->createColumn(
                'tl_metamodel_attribute',
                'country_get',
                'text NULL'
            );
        }

        if ($this->fieldExists('tl_metamodel_attribute', 'get_land')) {
            $this->connection->createQueryBuilder()
                ->update('tl_metamodel_attribute', 't')
                ->set('t.country_get', 't.get_land')
                ->execute();

            $this->tableManipulator->dropColumn('tl_metamodel_attribute', 'get_land');
        }

        if (!$this->fieldExists('tl_metamodel_attribute', 'countrymode')) {
            $this->tableManipulator->createColumn(
                'tl_metamodel_attribute',
                'countrymode',
                'varchar(255) NOT NULL default \'\''
            );
        }


        return new MigrationResult(true, 'Adjusted table tl_metamodel_attribute with countrymode and country_get');
    }

    /**
     * Check is a table column exists.
     *
     * @param string $strTableName  Table name.
     * @param string $strColumnName Column name.
     *
     * @return bool
     */
    private function fieldExists($strTableName, $strColumnName)
    {
        /** @var Column[] $columns */
        $columns = [];
        // The schema manager return the column list with lowercase keys, wo got to use the real names.
        \array_map(
            function (Column $column) use (&$columns) {
                $columns[$column->getName()] = $column;
            },
            $this->connection->getSchemaManager()->listTableColumns($strTableName)
        );

        return isset($columns[$strColumnName]);
    }
}
