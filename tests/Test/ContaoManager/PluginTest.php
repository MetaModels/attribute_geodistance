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

namespace MetaModels\AttributeGeoDistanceBundle\Test\ContaoManager;

use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use MetaModels\AttributeGeoDistanceBundle\ContaoManager\Plugin;
use MetaModels\AttributeGeoDistanceBundle\MetaModelsAttributeGeoDistanceBundle;
use MetaModels\CoreBundle\MetaModelsCoreBundle;
use MetaModels\FilterPerimetersearchBundle\MetaModelsFilterPerimetersearchBundle;
use PHPUnit\Framework\TestCase;

/**
 * Class PluginTest
 *
 * @covers \MetaModels\AttributeGeoDistanceBundle\ContaoManager\Plugin
 */
class PluginTest extends TestCase
{
    /**
     * Test get bundles.
     *
     * @covers \MetaModels\AttributeGeoDistanceBundle\ContaoManager\Plugin::getBundles
     */
    public function testGetBundles()
    {
        $plugin = new Plugin();
        $parser = $this->getMockBuilder(ParserInterface::class)->getMock();

        $bundleConfig = BundleConfig::create(MetaModelsAttributeGeoDistanceBundle::class)
            ->setLoadAfter(
                [
                    MetaModelsCoreBundle::class,
                    MetaModelsFilterPerimetersearchBundle::class
                ]
            )
            ->setReplace(['metamodelsattribute_geodistance']);

        $bundles = $plugin->getBundles($parser);
        self::assertCount(1, $bundles);
        self::assertSame($bundleConfig->getName(), $bundles[0]->getName());
        self::assertSame($bundleConfig->getReplace(), $bundles[0]->getReplace());
        self::assertSame($bundleConfig->getLoadAfter(), $bundles[0]->getLoadAfter());
    }
}
