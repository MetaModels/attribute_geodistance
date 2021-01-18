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
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2021 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_geodistance/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeGeoDistanceBundle\Attribute;

use Contao\CoreBundle\Framework\Adapter;
use Doctrine\DBAL\Connection;
use MetaModels\Attribute\AbstractSimpleAttributeTypeFactory;
use MetaModels\Helper\TableManipulator;

/**
 * Attribute type factory for geodistance attributes.
 */
class AttributeTypeFactory extends AbstractSimpleAttributeTypeFactory
{
    /**
     * The input provider.
     *
     * @var Adapter
     */
    private $input;

    /**
     * {@inheritDoc}
     */
    public function __construct(Connection $connection, TableManipulator $tableManipulator, Adapter $input = null)
    {
        parent::__construct($connection, $tableManipulator);

        $this->input = $input;

        $this->typeName  = 'geodistance';
        $this->typeIcon  = 'bundles/metamodelsattributegeodistance/image/geodistance.png';
        $this->typeClass = GeoDistance::class;
    }

    /**
     * {@inheritDoc}
     */
    public function createInstance($information, $metaModel)
    {
        return new $this->typeClass($metaModel, $information, $this->connection, $this->tableManipulator, $this->input);
    }
}
