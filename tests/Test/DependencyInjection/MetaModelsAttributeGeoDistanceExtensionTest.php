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
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_geodistance/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeGeoDistanceBundle\Test\DependencyInjection;

use MetaModels\AttributeGeoDistanceBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeGeoDistanceBundle\DependencyInjection\MetaModelsAttributeGeoDistanceExtension;
use MetaModels\AttributeGeoDistanceBundle\EventListener\AttributeListener;
use MetaModels\AttributeGeoDistanceBundle\EventListener\LookUpServiceListener;
use MetaModels\AttributeGeoDistanceBundle\Migration\AddCountryMigration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * @covers \MetaModels\AttributeGeoDistanceBundle\DependencyInjection\MetaModelsAttributeGeoDistanceExtension
 *
 * @SuppressWarnings(PHPMD.LongClassName)
 */
final class MetaModelsAttributeGeoDistanceExtensionTest extends TestCase
{
    public function testInstantiation(): void
    {
        $extension = new MetaModelsAttributeGeoDistanceExtension();

        $this->assertInstanceOf(MetaModelsAttributeGeoDistanceExtension::class, $extension);
        $this->assertInstanceOf(ExtensionInterface::class, $extension);
    }

    public function testFactoryIsRegistered(): void
    {
        $container = new ContainerBuilder();

        $extension = new MetaModelsAttributeGeoDistanceExtension();
        $extension->load([], $container);

        self::assertTrue($container->hasDefinition('metamodels.attribute_geodistance.factory'));
        $definition = $container->getDefinition('metamodels.attribute_geodistance.factory');
        self::assertCount(1, $definition->getTag('metamodels.attribute_factory'));
        // phpcs:disable
        self::assertTrue($container->hasDefinition('metamdodels.attribute_geodistance.event_listener.attribute_listener'));
        $definition = $container->getDefinition('metamdodels.attribute_geodistance.event_listener.attribute_listener');
        self::assertCount(1, $definition->getTag('kernel.event_listener'));

        self::assertTrue($container->hasDefinition('metamdodels.attribute_geodistance.event_listener.look_up_service_listener'));
        $definition = $container->getDefinition('metamdodels.attribute_geodistance.event_listener.look_up_service_listener');
        self::assertCount(1, $definition->getTag('kernel.event_listener'));
        // phpcs:enable
        self::assertTrue($container->hasDefinition(AddCountryMigration::class));
        $definition = $container->getDefinition(AddCountryMigration::class);
        self::assertCount(1, $definition->getTag('contao.migration'));
    }
}
