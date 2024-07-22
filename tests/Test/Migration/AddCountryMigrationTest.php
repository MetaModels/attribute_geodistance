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
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-20234 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_geodistance/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

declare(strict_types=1);

namespace MetaModels\AttributeGeoDistanceBundle\Test\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\TextType;
use MetaModels\AttributeGeoDistanceBundle\Migration\AddCountryMigration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MetaModels\AttributeGeoDistanceBundle\Migration\AddCountryMigration
 */
final class AddCountryMigrationTest extends TestCase
{
    public function testName(): void
    {
        $connection = $this->createMock(Connection::class);
        $migration  = new AddCountryMigration($connection);

        self::assertSame(
            'Add countrymode and country_get in MetaModels attribute table if not exist.',
            $migration->getName()
        );
    }

    public function runConfiguration(): \Generator
    {
        yield 'required tables not exist' => [
            (object) [
                'requiredTablesExist'   => false,
                'columnCountryGetExist' => false,
                'columnGetLandExist'    => false,
                'shouldRun'             => false,
            ]
        ];

        yield 'column get_land does not exist' => [
            (object) [
                'requiredTablesExist'   => true,
                'columnCountryGetExist' => false,
                'columnGetLandExist'    => false,
                'shouldRun'             => false,
            ]
        ];

        yield 'column country_get exist' => [
            (object) [
                'requiredTablesExist'   => true,
                'columnCountryGetExist' => true,
                'columnGetLandExist'    => false,
                'shouldRun'             => false,
            ]
        ];

        yield 'migration run' => [
            (object) [
                'requiredTablesExist'   => true,
                'columnCountryGetExist' => false,
                'columnGetLandExist'    => true,
                'shouldRun'             => true,
            ]
        ];
    }

    /**
     * @dataProvider runConfiguration
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function testRun(object $configuration): void
    {
        $connection   = $this->createMock(Connection::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $plattform    = $this->getMockBuilder(AbstractPlatform::class)->disableOriginalConstructor()->getMock();
        $manager      = $this
            ->getMockBuilder(AbstractSchemaManager::class)
            ->setConstructorArgs([$connection, $plattform])
            ->onlyMethods(['tablesExist', 'introspectTable', 'listTableColumns', 'alterTable'])
            ->getMockForAbstractClass();

        $connection
            ->expects($configuration->shouldRun ? self::exactly(2) : self::never())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects($configuration->shouldRun ? self::exactly(2) : self::never())
            ->method('update')
            ->willReturnCallback(static function (string $tableName, string $alias) use ($queryBuilder): QueryBuilder {
                self::assertSame('tl_metamodel_attribute', $tableName);
                self::assertSame('t', $alias);
                static $execCount = 0;
                switch (++$execCount) {
                    case 1:
                    case 2:
                        return $queryBuilder;
                    default:
                }
                self::fail('Called more than two times!');
            });
        $queryBuilder
            ->expects($configuration->shouldRun ? self::exactly(2) : self::never())
            ->method('set')
            ->willReturnCallback(static function (string $columnName, string $value) use ($queryBuilder): QueryBuilder {
                static $execCount = 0;
                switch (++$execCount) {
                    case 1:
                        self::assertSame('t.country_get', $columnName);
                        self::assertSame('null', $value);
                        return $queryBuilder;
                    case 2:
                        self::assertSame('t.countrymode', $columnName);
                        self::assertSame('"get"', $value);
                        return $queryBuilder;
                    default:
                }
                self::fail('Called more than two times!');
            });
        $queryBuilder
            ->expects($configuration->shouldRun ? self::exactly(2) : self::never())
            ->method('where')
            ->willReturnCallback(static function (string $filter) use ($queryBuilder): QueryBuilder {
                static $execCount = 0;
                switch (++$execCount) {
                    case 1:
                        self::assertSame('t.country_get = ""', $filter);
                        return $queryBuilder;
                    case 2:
                        self::assertSame('t.country_get != ""', $filter);
                        return $queryBuilder;
                    default:
                }
                self::fail('Called more than two times!');
            });
        $queryBuilderExecuted             = 0;
        $setCountryModeExecuted           = false;
        $updateColumnDefaultValueExecuted = false;
        $queryBuilder
            ->expects($configuration->shouldRun ? self::exactly(1) : self::never())
            ->method('executeStatement')
            ->willReturnCallback(
                function () use (
                    &$queryBuilderExecuted,
                    &$setCountryModeExecuted,
                    &$updateColumnDefaultValueExecuted
                ): int {
                    $queryBuilderExecuted++;

                    switch ($queryBuilderExecuted) {
                        case 1:
                            $updateColumnDefaultValueExecuted = true;
                            return 1;
                        case 2:
                            $setCountryModeExecuted = true;
                            return 1;
                        default:
                    }
                    return 0;
                }
            );

        $manager
            ->expects(self::once())
            ->method('tablesExist')
            ->with(['tl_metamodel', 'tl_metamodel_attribute'])
            ->willReturn($configuration->requiredTablesExist);

        $count = 0;
        if ($configuration->requiredTablesExist) {
            $count++;
            if ($configuration->columnGetLandExist) {
                $count++;
            }
        }

        $listTableColumns = [
            'country_get' =>
                (new Column('country_get', new TextType()))
                    ->setLength(AbstractMySQLPlatform::LENGTH_LIMIT_TEXT)
                    ->setNotnull(false)
                    ->setDefault(null),
            'get_land'    =>
                (new Column('get_land', new TextType()))
                    ->setLength(AbstractMySQLPlatform::LENGTH_LIMIT_TEXT)
                    ->setNotnull(false)
                    ->setDefault(null)
        ];
        if (!$configuration->columnCountryGetExist) {
            unset($listTableColumns['country_get']);
        }
        if (!$configuration->columnGetLandExist) {
            unset($listTableColumns['get_land']);
        }
        $manager
            ->expects($count ? self::exactly($count) : self::never())
            ->method('listTableColumns')
            ->with('tl_metamodel_attribute')
            ->willReturn($listTableColumns);
        $table = new Table(
            'tl_metamodel_attribute',
            $listTableColumns
        );

        $manager
            ->expects($configuration->shouldRun ? self::once() : self::never())
            ->method('introspectTable')
            ->with('tl_metamodel_attribute')
            ->willReturn($table);

        /** @var TableDiff $tableDiff */
        $tableDiff = null;
        $manager
            ->expects($configuration->shouldRun ? self::once() : self::never())
            ->method('alterTable')
            ->willReturnCallback(
                function (object $argument) use (&$tableDiff) {
                    $this->assertInstanceOf(TableDiff::class, $argument);
                    $tableDiff = $argument;
                }
            );

        $connection
            ->expects(
                $configuration->requiredTablesExist ? self::exactly($configuration->shouldRun ? 4 : 2) : self::once()
            )
            ->method('createSchemaManager')
            ->willReturn($manager);

        $migration = new AddCountryMigration($connection);
        self::assertSame($configuration->shouldRun, $migration->shouldRun());
        self::assertNull($tableDiff);
        self::assertSame(0, $queryBuilderExecuted);
        self::assertFalse($updateColumnDefaultValueExecuted);
        self::assertFalse($setCountryModeExecuted);

        if (!$configuration->shouldRun) {
            return;
        }

        $migrationResult = $migration->run();
        self::assertTrue($migrationResult->isSuccessful());
        self::assertSame(
            'Adjusted table tl_metamodel_attribute with countrymode and country_get',
            $migrationResult->getMessage()
        );
        self::assertSame('tl_metamodel_attribute', $tableDiff->getOldTable()->getName());
        self::assertCount(1, $tableDiff->addedColumns);
        self::assertCount(1, $tableDiff->changedColumns);
        self::assertSame(1, $queryBuilderExecuted);
        self::assertTrue($updateColumnDefaultValueExecuted);
        self::assertFalse($setCountryModeExecuted);
    }
}
