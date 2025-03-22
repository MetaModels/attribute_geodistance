<?php

/**
 * This file is part of MetaModels/attribute_geodistance.
 *
 * (c) 2012-2014 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_geodistance
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_geodistance/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeGeoDistanceBundle\ContaoManager;

use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use MetaModels\AttributeGeoDistanceBundle\MetaModelsAttributeGeoDistanceBundle;
use MetaModels\CoreBundle\MetaModelsCoreBundle;
use MetaModels\FilterPerimetersearchBundle\MetaModelsFilterPerimetersearchBundle;

/**
 * Contao Manager plugin.
 */
class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(MetaModelsAttributeGeoDistanceBundle::class)
                ->setLoadAfter(
                    [
                        MetaModelsCoreBundle::class,
                        MetaModelsFilterPerimetersearchBundle::class
                    ]
                )
                ->setReplace(['metamodelsattribute_geodistance'])
        ];
    }
}
