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
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_geodistance/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

/**
 * Table tl_metamodel_attribute
 */
$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['geodistance extends _simpleattribute_'] = [
    '+parameter' => ['get_geo', 'countrymode'],
    '+data'      => ['datamode'],
];
// Add the lookup service if the filter perimeter search is available.
if (count((array) $GLOBALS['METAMODELS']['filters']['perimetersearch'])) {
    $GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['geodistance extends _simpleattribute_']['+data'][] =
        'lookupservice';
}

// Subpalettes.
$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metasubselectpalettes']['datamode']['single']    =
    ['single_attr_id'];
$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metasubselectpalettes']['datamode']['multi']     = [
    'first_attr_id',
    'second_attr_id'
];
$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metasubselectpalettes']['countrymode']['preset'] =
    ['country_preset'];
$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metasubselectpalettes']['countrymode']['get']    =
    ['country_get'];

// Fields.
$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['get_geo'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['get_geo'],
    'exclude'   => true,
    'inputType' => 'text',
    'sql'       => 'varchar(255) NOT NULL default \'\'',
    'eval'      => [
        'tl_class' => 'w50',
        'mandatory'          => true
    ]
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['countrymode'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['countrymode'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => ['none', 'preset', 'get'],
    'reference' => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['countrymode_options'],
    'eval'      => [
        'tl_class'       => 'clr w50 w50x',
        'doNotSaveEmpty' => true,
        'alwaysSave'     => true,
        'submitOnChange' => true,
        'mandatory'      => true
    ],
    'sql'      => 'varchar(255) NOT NULL default \'\''
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['country_preset'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['country_preset'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => [
        'tl_class'  => 'w50 w50x',
        'mandatory' => true
    ],
    'sql'      => 'text NULL'
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['country_get'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_filtersetting']['country_get'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => [
        'tl_class'  => 'w50 w50x',
        'mandatory' => true
    ],
    'sql'      => 'text NULL'
];

// Add the lookup service if the filter perimeter search is available.
if (count((array) $GLOBALS['METAMODELS']['filters']['perimetersearch'])) {
    $GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['lookupservice'] = [
        'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['lookupservice'],
        'exclude'   => true,
        'inputType' => 'multiColumnWizard',
        'sql'       => 'text NULL',
        'eval'      => [
            'tl_class'     => 'clr',
            'helpwizard'   => true,
            'columnFields' => [
                'lookupservice' => [
                    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['lookupservice_service'],
                    'exclude'   => true,
                    'inputType' => 'select',
                    'eval'      => [
                        'includeBlankOption' => true,
                        'mandatory'          => true,
                        'chosen'             => true,
                        'style'              => 'width:250px'
                    ]
                ],
                'apiToken' => [
                    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['lookupservice_api_token'],
                    'exclude'   => true,
                    'inputType' => 'text',
                    'eval'      => [
                        'tl_class' => 'w50'
                    ]
                ]
            ]
        ],
        'explanation' => 'attribute_lookupservice'
    ];
}

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['datamode'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['datamode'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => ['single', 'multi'],
    'reference' => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['datamode_options'],
    'sql'       => 'varchar(255) NOT NULL default \'\'',
    'eval'      => [
        'doNotSaveEmpty'     => true,
        'alwaysSave'         => true,
        'submitOnChange'     => true,
        'includeBlankOption' => true,
        'mandatory'          => true,
        'tl_class'           => 'clr'
    ]
];


$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['single_attr_id'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['single_attr_id'],
    'exclude'   => true,
    'inputType' => 'select',
    'sql'       => 'varchar(255) NOT NULL default \'\'',
    'eval'      => [
        'doNotSaveEmpty'     => true,
        'alwaysSave'         => true,
        'submitOnChange'     => true,
        'includeBlankOption' => true,
        'mandatory'          => true,
        'tl_class'           => 'w50',
        'chosen'             => true
    ]
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['first_attr_id'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['first_attr_id'],
    'exclude'   => true,
    'inputType' => 'select',
    'sql'       => 'varchar(255) NOT NULL default \'\'',
    'eval'      => [
        'doNotSaveEmpty'     => true,
        'alwaysSave'         => true,
        'submitOnChange'     => true,
        'includeBlankOption' => true,
        'mandatory'          => true,
        'tl_class'           => 'w50',
        'chosen'             => true
    ]
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['second_attr_id'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['second_attr_id'],
    'exclude'   => true,
    'inputType' => 'select',
    'sql'       => 'varchar(255) NOT NULL default \'\'',
    'eval'      => [
        'doNotSaveEmpty'     => true,
        'alwaysSave'         => true,
        'submitOnChange'     => true,
        'includeBlankOption' => true,
        'mandatory'          => true,
        'tl_class'           => 'w50',
        'chosen'             => true
    ]
];
