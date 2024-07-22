<?php

/**
 * This file is part of MetaModels/attribute_geodistance.
 *
 * (c) 2012-2023 The MetaModels team.
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
 * @copyright  2012-2023 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_geodistance/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeGeoDistanceBundle\EventListener;

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use ContaoCommunityAlliance\DcGeneral\Data\ModelInterface;
use ContaoCommunityAlliance\DcGeneral\InputProviderInterface;
use MetaModels\Factory;
use MetaModels\Filter\Setting\FilterSettingFactory;
use MetaModels\IMetaModel;

/**
 * This class provides the attribute options and encodes and decodes the attribute id.
 */
class AttributeListener
{
    use BaseTrait;

    /**
     * The metamodels factory.
     *
     * @var Factory
     */
    private Factory $factory;

    /**
     * The filter factory.
     *
     * @var FilterSettingFactory
     */
    private FilterSettingFactory $filterFactory;

    /**
     * AttributeListener constructor.
     *
     * @param Factory              $factory       The factory.
     * @param FilterSettingFactory $filterFactory The filter factory.
     */
    public function __construct(Factory $factory, FilterSettingFactory $filterFactory)
    {
        $this->factory       = $factory;
        $this->filterFactory = $filterFactory;
    }

    /**
     * Prepares a option list with alias => name connection for all attributes.
     *
     * This is used in the attr_id select box.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     */
    public function getAttributeIdOptions(GetPropertyOptionsEvent $event)
    {
        // Check the context.
        $allowedProperties = ['first_attr_id', 'second_attr_id', 'single_attr_id'];
        if (
            !$this->isAllowedProperty($event, 'tl_metamodel_attribute', $allowedProperties)
        ) {
            return;
        }

        $model         = $event->getModel();
        $metaModelId   = $model->getProperty('pid');
        $inputProvider = $event->getEnvironment()->getInputProvider();
        assert($inputProvider instanceof InputProviderInterface);
        if (!$metaModelId) {
            $metaModelId = ModelId::fromSerialized(
                $inputProvider->getValue('pid')
            )->getId();
        }

        $metaModelName = $this->factory->translateIdToMetaModelName($metaModelId);
        $metaModel     = $this->factory->getMetaModel($metaModelName);

        if (!$metaModel) {
            return;
        }

        if (null === ($propertyName = $event->getPropertyName())) {
            return;
        }

        $event->setOptions($this->fetchAttributeIdOptions($model, $propertyName, $metaModelId));
    }

    /**
     * Fetch the options for the attribute id.
     *
     * @param ModelInterface $model        The model.
     * @param string         $propertyName The name of the property.
     * @param string         $metaModelId  The id of the metamodel.
     *
     * @return array
     */
    private function fetchAttributeIdOptions(ModelInterface $model, $propertyName, $metaModelId): array
    {
        $metaModelName = $this->factory->translateIdToMetaModelName($metaModelId);
        $metaModel     = $this->factory->getMetaModel($metaModelName);
        assert($metaModel instanceof IMetaModel);
        $result        = [];

        $typeFactory = $this->filterFactory->getTypeFactory($model->getProperty('type'));

        $typeFilter = [];
        if ($typeFactory) {
            $typeFilter = $typeFactory->getKnownAttributeTypes() ?? [];
        }

        if ('single_attr_id' === $propertyName) {
            $typeFilter = ['geolocation'];
        } else {
            $key = \array_search('geolocation', $typeFilter);
            if (false !== $key) {
                unset($typeFilter[$key]);
            }
        }

        foreach ($metaModel->getAttributes() as $attribute) {
            $typeName = $attribute->get('type');
            if ($typeFilter && (!\in_array($typeName, $typeFilter))) {
                continue;
            }
            $selectValue          = $attribute->getColName();
            $result[$selectValue] = $attribute->getName() . ' [' . $typeName . ']';
        }

        return $result;
    }
}
