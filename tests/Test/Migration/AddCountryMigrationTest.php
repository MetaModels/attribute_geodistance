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
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2021 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_geodistance/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

declare(strict_types=1);

namespace MetaModels\AttributeGeoDistanceBundle\Test\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
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
     */
    public function testRun(object $configuration): void
    {
        $connection = $this->createMock(Connection::class);
        $manager    = $this
            ->getMockBuilder(AbstractSchemaManager::class)
            ->setConstructorArgs([$connection])
            ->onlyMethods(['tablesExist', 'listTableColumns', 'listTableDetails', 'alterTable'])
            ->getMockForAbstractClass();

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
                (new Column('country_get', new TextType()))->setLength(MySqlPlatform::LENGTH_LIMIT_TEXT)->setNotnull(false)->setDefault(null)
        ];
        if ($configuration->shouldRun) {
            unset($listTableColumns['country_get']);
        }
        $manager
            ->expects($configuration->requiredTablesExist ? self::once() : self::never())
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
            ->expects($configuration->requiredTablesExist ? self::exactly($configuration->shouldRun ? 3 : 2) : self::once())
            ->method('getSchemaManager')
            ->willReturn($manager);

        $migration = new AddCountryMigration($connection);
        self::assertSame($configuration->shouldRun, $migration->shouldRun());
        self::assertNull($tableDiff);

        if (!$configuration->shouldRun) {
            return;
        }

        $migrationResult = $migration->run();
        self::assertTrue($migrationResult->isSuccessful());
        self::assertSame('Adjusted table tl_metamodel_attribute with countrymode and country_get', $migrationResult->getMessage());
        self::assertSame('tl_metamodel_attribute', $tableDiff->name);
        self::assertCount(1, $tableDiff->addedColumns);
        self::assertCount(1, $tableDiff->changedColumns);
    }
}
