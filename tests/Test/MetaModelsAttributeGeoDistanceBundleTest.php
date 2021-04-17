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

namespace MetaModels\AttributeGeoDistanceBundle\Test;

use MetaModels\AttributeGeoDistanceBundle\DependencyInjection\MetaModelsAttributeGeoDistanceExtension;
use MetaModels\AttributeGeoDistanceBundle\MetaModelsAttributeGeoDistanceBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Resource\ComposerResource;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class MetaModelsAttributeGeoDistanceBundleTest
 *
 * @covers \MetaModels\AttributeGeoDistanceBundle\MetaModelsAttributeGeoDistanceBundle
 */
class MetaModelsAttributeGeoDistanceBundleTest extends TestCase
{
    public function testCanBeInstantiated()
    {
        $bundle = new MetaModelsAttributeGeoDistanceBundle();

        self::assertInstanceOf(MetaModelsAttributeGeoDistanceBundle::class, $bundle);
    }

    public function testReturnsTheContainerExtension()
    {
        $extension = (new MetaModelsAttributeGeoDistanceBundle())->getContainerExtension();

        self::assertInstanceOf(MetaModelsAttributeGeoDistanceExtension::class, $extension);
    }

    /**
     * @covers \MetaModels\AttributeGeoDistanceBundle\DependencyInjection\MetaModelsAttributeGeoDistanceExtension::load
     */
    public function testLoadExtensionConfiguration()
    {
        $extension = (new MetaModelsAttributeGeoDistanceBundle())->getContainerExtension();
        $container = new ContainerBuilder();

        $extension->load([], $container);

        self::assertInstanceOf(FileResource::class, $container->getResources()[0]);
        self::assertSame(
            \dirname(__DIR__, 2) . '/src/Resources/config/attribute-settings.yml',
            $container->getResources()[0]->getResource()
        );
        self::assertInstanceOf(FileResource::class, $container->getResources()[1]);
        self::assertSame(
            \dirname(__DIR__, 2) . '/src/Resources/config/listeners.yml',
            $container->getResources()[1]->getResource()
        );
        self::assertInstanceOf(FileResource::class, $container->getResources()[2]);
        self::assertSame(
            \dirname(__DIR__, 2) . '/src/Resources/config/services.yml',
            $container->getResources()[2]->getResource()
        );
    }
}
