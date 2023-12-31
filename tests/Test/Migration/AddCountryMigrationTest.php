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
                'shouldRun'             => false,
                'columnCountryGetExist' => false
            ]
        ];

        yield 'column country_get exist' => [
            (object) [
                'requiredTablesExist'   => true,
                'shouldRun'             => false,
                'columnCountryGetExist' => true
            ]
        ];

        yield 'migration run' => [
            (object) [
                'requiredTablesExist'   => true,
                'shouldRun'             => true,
                'columnCountryGetExist' => false
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
            ->onlyMethods(['tablesExist', 'listTableColumns', 'listTableDetails', 'alterTable'])
            ->getMockForAbstractClass();

        $connection
            ->expects($configuration->shouldRun ? self::exactly(2) : self::never())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects($configuration->shouldRun ? self::exactly(2) : self::never())
            ->method('update')
            ->withConsecutive(['tl_metamodel_attribute', 't'], ['tl_metamodel_attribute', 't'])
            ->willReturn($queryBuilder);
        $queryBuilder
            ->expects($configuration->shouldRun ? self::exactly(2) : self::never())
            ->method('set')
            ->withConsecutive(['t.country_get', 'null'], ['t.countrymode', '"get"'])
            ->willReturn($queryBuilder);
        $queryBuilder
            ->expects($configuration->shouldRun ? self::exactly(2) : self::never())
            ->method('where')
            ->withConsecutive(['t.country_get = ""'], ['t.country_get != ""'])
            ->willReturn($queryBuilder);
        $queryBuilderExecuted             = 0;
        $setCountryModeExecuted           = false;
        $updateColumnDefaultValueExecuted = false;
        $queryBuilder
            ->expects($configuration->shouldRun ? self::exactly(2) : self::never())
            ->method('execute')
            ->willReturnCallback(
                function () use (&$queryBuilderExecuted, &$setCountryModeExecuted, &$updateColumnDefaultValueExecuted) {
                    $queryBuilderExecuted++;

                    switch ($queryBuilderExecuted) {
                        case 1:
                            $updateColumnDefaultValueExecuted = true;
                            break;
                        case 2:
                            $setCountryModeExecuted = true;
                            break;
                        default:
                    }
                }
            );

        $manager
            ->expects(self::once())
            ->method('tablesExist')
            ->with(['tl_metamodel', 'tl_metamodel_attribute'])
            ->willReturn($configuration->requiredTablesExist);

        $manager
            ->expects($configuration->shouldRun ? self::once() : self::never())
            ->method('listTableDetails')
            ->with('tl_metamodel_attribute')
            ->willReturn(new Table('tl_metamodel_attribute'));

        $listTableColumns = [
            'country_get' =>
                (new Column('country_get', new TextType()))->setLength(MySqlPlatform::LENGTH_LIMIT_TEXT)->setNotnull(
                    false
                )->setDefault(null),
            'get_land'    =>
                (new Column('get_land', new TextType()))->setLength(MySqlPlatform::LENGTH_LIMIT_TEXT)
                    ->setNotnull(false)
                    ->setDefault(null)
        ];
        if ($configuration->shouldRun) {
            unset($listTableColumns['country_get']);
        }
        $manager
            ->expects($configuration->requiredTablesExist ? self::exactly(2) : self::never())
            ->method('listTableColumns')
            ->with('tl_metamodel_attribute')
            ->willReturn($listTableColumns);

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
                $configuration->requiredTablesExist ? self::exactly($configuration->shouldRun ? 4 : 3) : self::once()
            )
            ->method('getSchemaManager')
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
        self::assertSame('tl_metamodel_attribute', $tableDiff->getOldTable());
        self::assertCount(1, $tableDiff->addedColumns);
        self::assertCount(1, $tableDiff->changedColumns);
        self::assertSame(2, $queryBuilderExecuted);
        self::assertTrue($updateColumnDefaultValueExecuted);
        self::assertTrue($setCountryModeExecuted);
    }
}
