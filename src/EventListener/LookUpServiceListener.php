<?php

/**
 * This file is part of MetaModels/attribute_geodistance.
 *
 * (c) 2012-2022 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_geodistance
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2022 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_geodistance/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeGeoDistanceBundle\EventListener;

use MenAtWork\MultiColumnWizardBundle\Event\GetOptionsEvent;
use MetaModels\FilterPerimetersearchBundle\FilterHelper\Coordinates;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This class provides the attribute options and encodes and decodes the attribute id.
 */
class LookUpServiceListener
{
    use BaseTrait;
    /**
     * The translator.
     *
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * The constructor.
     *
     * @param TranslatorInterface $translator The translator.
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Get a list with all supported resolver class for a geo lookup.
     *
     * @param GetOptionsEvent $event The event.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function getResolverClass(GetOptionsEvent $event)
    {
        // Check the context.
        $allowedProperties = ['lookupservice'];
        if (!$this->isAllowedProperty($event, 'tl_metamodel_attribute', $allowedProperties)
            || ('lookupservice' !== $event->getSubPropertyName())
        ) {
            return;
        }

        $resolveClasses = \array_merge(
            ['coordinates' => Coordinates::class],
            (array) $GLOBALS['METAMODELS']['filters']['perimetersearch']['resolve_class']
        );

        $domain  = 'tl_metamodel_attribute';
        $options = [];
        foreach (\array_keys($resolveClasses) as $name) {
            $options[$name] = $this->translator->trans($domain . '.perimetersearch.' . $name, [], 'contao_' . $domain);
        }

        $event->setOptions($options);
    }
}
