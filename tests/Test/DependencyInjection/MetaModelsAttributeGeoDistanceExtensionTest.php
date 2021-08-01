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
 * @copyright  2012-2021 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_geodistance/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeGeoDistanceBundle\Test\DependencyInjection;

use MetaModels\AttributeGeoDistanceBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeGeoDistanceBundle\DependencyInjection\MetaModelsAttributeGeoDistanceExtension;
use MetaModels\AttributeGeoDistanceBundle\EventListener\AttributeListener;
use MetaModels\AttributeGeoDistanceBundle\EventListener\LookUpServiceListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * @covers \MetaModels\AttributeGeoDistanceBundle\DependencyInjection\MetaModelsAttributeGeoDistanceExtension
 *
 * @SuppressWarnings(PHPMD.LongClassName)
 */
final class MetaModelsAttributeGeoDistanceExtensionTest extends TestCase
{
    /**
     * Test that extension can be instantiated.
     *
     * @return void
     */
    public function testInstantiation()
    {
        $extension = new MetaModelsAttributeGeoDistanceExtension();

        $this->assertInstanceOf(MetaModelsAttributeGeoDistanceExtension::class, $extension);
        $this->assertInstanceOf(ExtensionInterface::class, $extension);
    }

    /**
     * Test that the services are loaded.
     *
     * @return void
     */
    public function testFactoryIsRegistered(): void
    {
        $container = $this->getMockBuilder(ContainerBuilder::class)->getMock();

        $container
            ->expects($this->exactly(3))
            ->method('setDefinition')
            ->withConsecutive(
                [
                    'metamodels.attribute_geodistance.factory',
                    $this->callback(
                        function ($value) {
                            /** @var Definition $value */
                            $this->assertInstanceOf(Definition::class, $value);
                            $this->assertEquals(AttributeTypeFactory::class, $value->getClass());
                            $this->assertCount(1, $value->getTag('metamodels.attribute_factory'));

                            return true;
                        }
                    )
                ],
                [
                    'metamdodels.attribute_geodistance.event_listener.attribute_listener',
                    $this->callback(
                        function ($value) {
                            /** @var Definition $value */
                            $this->assertInstanceOf(Definition::class, $value);
                            $this->assertEquals(AttributeListener::class, $value->getClass());
                            $this->assertCount(1, $value->getTag('kernel.event_listener'));

                            return true;
                        }
                    )
                ],
                [
                    'metamdodels.attribute_geodistance.event_listener.look_up_service_listener',
                    $this->callback(
                        function ($value) {
                            /** @var Definition $value */
                            $this->assertInstanceOf(Definition::class, $value);
                            $this->assertEquals(LookUpServiceListener::class, $value->getClass());
                            $this->assertCount(1, $value->getTag('kernel.event_listener'));

                            return true;
                        }
                    )
                ]
            );

        $extension = new MetaModelsAttributeGeoDistanceExtension();
        $extension->load([], $container);
    }
}
