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
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2021 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_geodistance/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeGeoDistanceBundle\Test;

use MetaModels\AttributeGeoDistanceBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeGeoDistanceBundle\Attribute\GeoDistance;
use PHPUnit\Framework\TestCase;

/**
 * This class tests if the deprecated autoloader works.
 *
 * @covers \MetaModels\AttributeGeoDistanceBundle\DeprecatedAutoloader
 */
class DeprecatedAutoloaderTest extends TestCase
{
    /**
     * Textes of old classes to the new one.
     *
     * @var array
     */
    private static $classes = [
        'MetaModels\Attribute\GeoDistance\GeoDistance' => GeoDistance::class,
        'MetaModels\Attribute\GeoDistance\AttributeTypeFactory' => AttributeTypeFactory::class
    ];

    /**
     * Provide the text class map.
     *
     * @return array
     */
    public function provideAliasClassMap()
    {
        $values = [];

        foreach (static::$classes as $text => $class) {
            $values[] = [$text, $class];
        }

        return $values;
    }

    /**
     * Test if the deprecated classes are aliased to the new one.
     *
     * @param string $oldClass Old class name.
     * @param string $newClass New class name.
     *
     * @dataProvider provideAliasClassMap
     */
    public function testDeprecatedClassesAreAliases($oldClass, $newClass)
    {
        $this->assertTrue(class_exists($oldClass), sprintf('Class text "%s" is not found.', $oldClass));

        $oldClassReflection = new \ReflectionClass($oldClass);
        $newClassReflection = new \ReflectionClass($newClass);

        $this->assertSame($newClassReflection->getFileName(), $oldClassReflection->getFileName());
    }
}
