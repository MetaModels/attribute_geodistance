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
 * @package    MetaModels
 * @subpackage AttributeGeoDistanceBundle
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_geodistance/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['typeOptions']['geodistance'] = 'Geo Distance';

$GLOBALS['TL_LANG']['tl_metamodel_attribute']['get_geo']                 = [
    'GET-Parameter for address',
    'Here you can add the GET-Parameter name for the address lookup.'
];
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['get_land']                = [
    'GET-Parameter for country',
    'Here you can add the GET-Parameter name for the country lookup.'
];
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['datamode']                = [
    'Data mode',
    'Here you can choose if you have one single attribute or two attributes.'
];
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['single_attr_id']          = [
    'Attribute',
    'Choose the attribute with the latitude and longitude values.'
];
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['first_attr_id']           = [
    'Attribute - Latitude',
    'Choose the attribute for the latitude values.'
];
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['second_attr_id']          = [
    'Attribute - Longitude',
    'Choose the attribute for the longitude values.'
];
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['lookupservice_service']   = [
    'LookUp Services',
    'Here you can choose a look up service for resolving address data.'
];
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['lookupservice_api_token'] = [
    'API token',
    'Here you can add a the API token.'
];
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['countrymode']             = [
    'Countrymode',
    'Here you can choose how the language will used.'
];
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['country_preset']          = [
    'Country preset',
    'Here you can add a preset for the language.'
];
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['country_get']             = [
    'GET-Parameter for country',
    'Here you can add the GET-Parameter name for the country lookup.'
];

/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['parameter_legend'] = 'Address parameter';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['data_legend']      = 'Data settings';

/**
 * Options
 */
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['datamode_options']['single']    = 'Single Mode - One attribute';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['datamode_options']['multi']     = 'Multi Mode - Two attributes';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['countrymode_options']['none']   = 'None';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['countrymode_options']['preset'] = 'Preset by system';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['countrymode_options']['get']    = 'Use GET-Param';

/**
 * Lookup names
 */
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['perimetersearch']['coordinates']      = 'Coordinates input';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['perimetersearch']['google_maps']      = 'GoogleMaps lookup';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['perimetersearch']['open_street_maps'] = 'OpenStreetMap lookup';
