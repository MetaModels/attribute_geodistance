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
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_geodistance/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeGeoDistanceBundle\Attribute;

use Contao\CoreBundle\Framework\Adapter;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use MetaModels\Attribute\BaseComplex;
use MetaModels\Attribute\IAttribute;
use MetaModels\FilterPerimetersearchBundle\FilterHelper\Container;
use MetaModels\FilterPerimetersearchBundle\FilterHelper\Coordinates;
use MetaModels\FilterPerimetersearchBundle\Helper\HaversineSphericalDistance;
use MetaModels\Helper\TableManipulator;
use MetaModels\IMetaModel;

/**
 * This is the MetaModelAttribute class for handling geodistance fields.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GeoDistance extends BaseComplex
{
    /**
     * The database connection.
     *
     * @var Connection
     */
    private Connection $connection;

    /**
     * The table manipulator.
     *
     * @var TableManipulator
     */
    private TableManipulator $tableManipulator;

    /**
     * The input provider.
     *
     * @var Adapter
     */
    private Adapter $input;

    /**
     * Instantiate an MetaModel attribute.
     *
     * Note that you should not use this directly but use the factory classes to instantiate attributes.
     *
     * @param IMetaModel            $metaModel        The MetaModel instance this attribute belongs to.
     * @param array                 $data             The information array, for attribute information, refer to
     *                                                documentation of table tl_metamodel_attribute and documentation
     *                                                of the certain attribute classes for information what values are
     *                                                understood.
     * @param Connection|null       $connection       The database connection.
     * @param TableManipulator|null $tableManipulator The table manipulator.
     * @param Adapter|null          $input            The input provider.
     */
    public function __construct(
        IMetaModel $metaModel,
        array $data = [],
        Connection $connection = null,
        TableManipulator $tableManipulator = null,
        Adapter $input = null
    ) {
        parent::__construct($metaModel, $data);

        if (null === $connection) {
            // @codingStandardsIgnoreStart
            @trigger_error(
                'Connection is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            $connection = System::getContainer()->get('database_connection');
            assert($connection instanceof Connection);
        }
        $this->connection = $connection;

        if (null === $tableManipulator) {
            // @codingStandardsIgnoreStart
            @trigger_error(
                'Table manipulator is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd

            $tableManipulator = System::getContainer()->get('metamodels.table_manipulator');
            assert($tableManipulator instanceof TableManipulator);
        }
        $this->tableManipulator = $tableManipulator;

        if (null === $input) {
            // @codingStandardsIgnoreStart
            @\trigger_error(
                'Request is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            $input = System::getContainer()->get('contao.framework')?->getAdapter(Input::class);
            assert($input instanceof Adapter);
        }
        $this->input = $input;
    }

    /**
     * An internal list with values.
     *
     * @var array
     */
    protected static $data = [];

    /**
     * Run the geolocation and distance core function.
     *
     * @param array  $idList    The list of id's
     * @param string $direction The direction for sorting. either 'ASC' or 'DESC', as in plain SQL.
     *
     * @return array The new list of id's
     *
     * @throws \RuntimeException If something is missing.
     */
    protected function runGeodistance($idList, $direction)
    {
        // Get some settings.
        $getGeo  = $this->get('get_geo');
        $service = $this->get('lookupservice');

        // Check if we have a get param.
        if (empty($getGeo) || empty($service)) {
            throw new \RuntimeException('Missing informations for geo location.');
        }

        // Get the params.
        $geo  = $this->input->get($getGeo);
        $land = $this->getCountryInformation();

        // Check if we have some geo params.
        if (empty($geo) && (null === $land)) {
            throw new \RuntimeException('Missing geo parameter for geo location.');
        }

        return $this->matchIdList($idList, $direction);
    }

    /**
     * {@inheritdoc}
     */
    public function sortIds($idList, $strDirection)
    {
        // Check if we have some id.
        if (empty($idList)) {
            return $idList;
        }

        $direction = 'DESC' === \strtoupper($strDirection) ? 'DESC' : 'ASC';

        try {
            return $this->runGeodistance($idList, $direction);
        } catch (\Exception $e) {
            self::$data[$this->get('id')] = [];
            return $idList;
        }
    }

    /**
     * Match the id list.
     *
     * @param array  $idList    The list of ids.
     * @param string $direction The direction for sorting. either 'ASC' or 'DESC', as in plain SQL.
     *
     * @return array
     */
    private function matchIdList(array $idList, string $direction): array
    {
        $metaModel = $this->getMetaModel();

        // Get some settings.
        $getGeo = $this->get('get_geo');

        // Get the params.
        $geo  = $this->input->get($getGeo);
        $land = $this->getCountryInformation();

        try {
            // Get the geo data.
            $container = $this->lookupGeo($geo, $land);

            // Okay we cant find a entry. So search for nothing.
            if ((null === $container) || $container->hasError()) {
                return $idList;
            }

            if ('single' === $this->get('datamode')) {
                // Get the attribute.
                $attribute = $metaModel->getAttribute($this->get('single_attr_id'));

                // Search for the geolocation attribute.
                if ('geolocation' === $attribute->get('type')) {
                    $idList = $this->doSearchForAttGeolocation($container, $idList, $direction);
                }
            } elseif ('multi' === $this->get('datamode')) {
                // Get the attributes.
                $firstAttribute  = $metaModel->getAttribute($this->get('first_attr_id'));
                $secondAttribute = $metaModel->getAttribute($this->get('second_attr_id'));

                // Search for two simple attributes.
                $idList = $this
                    ->doSearchForTwoSimpleAtt($container, $idList, $firstAttribute, $secondAttribute, $direction);
            }
        } catch (\Exception $e) {
            // Should be never happened, just in case.
            return $idList;
        }

        // Base implementation, do not perform any sorting.
        return $idList;
    }

    /**
     * Run the search for the complex attribute geolocation.
     *
     * @param Container $container The container with all information.
     * @param array     $idList    A list with ids.
     * @param string    $direction The direction for sorting. either 'ASC' or 'DESC', as in plain SQL.
     *
     * @return array A list with all sorted id's.
     *
     * @see https://www.movable-type.co.uk/scripts/latlong.html
     */
    protected function doSearchForAttGeolocation($container, $idList, $direction)
    {
        // Calculate distance, bearing and more between Latitude/Longitude points
        $distanceCalculation = HaversineSphericalDistance::getFormulaAsQueryPart(
            $container->getLatitude(),
            $container->getLongitude(),
            $this->connection->quoteIdentifier('latitude'),
            $this->connection->quoteIdentifier('longitude'),
            2
        );

        $idField       = $this->connection->quoteIdentifier('id');
        $itemDistField = $this->connection->quoteIdentifier('item_dist');
        $attIdField    = $this->connection->quoteIdentifier('att_id');
        $builder       = $this->connection->createQueryBuilder();
        $builder
            ->select($idField . ', ' . $distanceCalculation . ' ' . $itemDistField)
            ->from($this->connection->quoteIdentifier('tl_metamodel_geolocation'))
            ->where($builder->expr()->in($idField, ':idList'))
            ->andWhere($builder->expr()->eq($attIdField, ':attributeID'))
            ->orderBy($itemDistField, $direction)
            ->setParameter('idList', $idList, ArrayParameterType::STRING)
            ->setParameter('attributeID', $this->getMetaModel()->getAttribute($this->get('single_attr_id'))->get('id'));

        $statement = $builder->executeQuery();

        if (!$statement->rowCount()) {
            return $idList;
        }

        $newIdList = [];
        foreach ($statement->fetchAllAssociative() as $item) {
            $newIdList[]                             = $item['id'];
            self::$data[$this->get('id')][$item['id']] = $item['item_dist'];
        }

        $diff = \array_diff($idList, $newIdList);

        return \array_merge($newIdList, $diff);
    }

    /**
     * Run the search for the complex attribute geolocation.
     *
     * @param Container  $container     The container with all information.
     * @param array      $idList        The list with the current ID's.
     * @param IAttribute $latAttribute  The attribute to filter on.
     * @param IAttribute $longAttribute The attribute to filter on.
     * @param string     $direction     The direction for sorting. either 'ASC' or 'DESC', as in plain SQL.
     *
     * @return array A list with all sorted id's.
     *
     * @see https://www.movable-type.co.uk/scripts/latlong.html
     */
    protected function doSearchForTwoSimpleAtt($container, $idList, $latAttribute, $longAttribute, $direction)
    {
        // Calculate distance, bearing and more between Latitude/Longitude points
        $distanceCalculation = HaversineSphericalDistance::getFormulaAsQueryPart(
            $container->getLatitude(),
            $container->getLongitude(),
            $this->connection->quoteIdentifier($latAttribute->getColName()),
            $this->connection->quoteIdentifier($longAttribute->getColName()),
            2
        );

        $idField       = $this->connection->quoteIdentifier('id');
        $itemDistField = $this->connection->quoteIdentifier('item_dist');
        $builder       = $this->connection->createQueryBuilder();
        $builder
            ->select($idField . ', ' . $distanceCalculation . ' ' . $itemDistField)
            ->from($this->connection->quoteIdentifier($this->getMetaModel()->getTableName()))
            ->where($builder->expr()->in($idField, ':idList'))
            ->orderBy($itemDistField, $direction)
            ->setParameter('idList', $idList, ArrayParameterType::STRING);
        $statement = $builder->executeQuery();

        if (!$statement->rowCount()) {
            return $idList;
        }

        $newIdList = [];
        foreach ($statement->fetchAllAssociative() as $item) {
            $newIdList[]                               = $item['id'];
            self::$data[$this->get('id')][$item['id']] = $item['item_dist'];
        }

        $diff = \array_diff($idList, $newIdList);

        return \array_merge($newIdList, $diff);
    }

    /**
     * User the provider classes to make a look up.
     *
     * @param string $address The full address to search for.
     * @param string $country The country as 2-letters form.
     *
     * @return Container|null Return the container with all information or null on error.
     */
    protected function lookupGeo($address, $country)
    {
        // Trim the data. Better!
        $address = \trim($address);
        $country = \trim($country);

        // First check cache.
        $cacheResult = $this->getFromCache($address, $country);
        if (null !== $cacheResult) {
            return $cacheResult;
        }

        // If there is no data from the cache ask google.
        $lookupServices = StringUtil::deserialize($this->get('lookupservice'), true);
        if (!count($lookupServices)) {
            return null;
        }

        foreach ($lookupServices as $lookupService) {
            try {
                $callback = $this->getObjectFromName($lookupService['lookupservice']);

                // Call the main function.
                if (null !== $callback) {
                    /** @var Container $result */
                    $result = $callback
                        ->getCoordinates(
                            null,
                            null,
                            null,
                            $country,
                            $address,
                            $lookupService['apiToken'] ?: null
                        );

                    // Check if we have a result.
                    if (!$result->hasError()) {
                        $this->addToCache($address, $country, $result);

                        return $result;
                    }
                }
            } catch (\RuntimeException $exc) {
                // Okay, we have an error try next one.
                continue;
            }
        }

        // When we reach this point, we have no result, so return false.
        return null;
    }

    /**
     * Try to get a object from the given class.
     *
     * @param string $lookupClassName The name of the class.
     *
     * @return null|object
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    protected function getObjectFromName($lookupClassName)
    {
        $resolveClasses = \array_merge(
            ['coordinates' => Coordinates::class],
            (array) $GLOBALS['METAMODELS']['filters']['perimetersearch']['resolve_class']
        );

        // Check if we know this class.
        if (!isset($resolveClasses[$lookupClassName])) {
            return null;
        }

        $reflectionName = $resolveClasses[$lookupClassName];
        $reflection     = new \ReflectionClass($reflectionName);

        // Fetch singleton instance.
        if ($reflection->hasMethod('getInstance')) {
            $getInstanceMethod = $reflection->getMethod('getInstance');

            // Create a new instance.
            if ($getInstanceMethod->isStatic()) {
                return $getInstanceMethod->invoke(null);
            }

            return $reflection->newInstance();
        }

        // Create a normal object.
        return $reflection->newInstance();
    }

    /**
     * Add data to the cache.
     *
     * @param string    $address The address which where use for the search.
     * @param string    $country The country.
     * @param Container $result  The container with all information.
     *
     * @return void
     *
     * @throws \Doctrine\DBAL\DBALException When insert fails.
     */
    protected function addToCache($address, $country, $result)
    {
        $this->connection->insert(
            $this->connection->quoteIdentifier('tl_metamodel_perimetersearch'),
            [
                $this->connection->quoteIdentifier('search')   => $address,
                $this->connection->quoteIdentifier('country')  => $country,
                $this->connection->quoteIdentifier('geo_lat')  => $result->getLatitude(),
                $this->connection->quoteIdentifier('geo_long') => $result->getLongitude()
            ]
        );
    }

    /**
     * Get data from cache.
     *
     * @param string $address The address which where use for the search.
     * @param string $country The country.
     *
     * @return Container|null
     */
    protected function getFromCache($address, $country)
    {
        $builder = $this->connection->createQueryBuilder();
        $builder
            ->select('*')
            ->from($this->connection->quoteIdentifier('tl_metamodel_perimetersearch'))
            ->where($builder->expr()->eq($this->connection->quoteIdentifier('search'), ':search'))
            ->andWhere($builder->expr()->eq($this->connection->quoteIdentifier('country'), ':country'))
            ->setParameter('search', $address)
            ->setParameter('country', $country);

        $statement = $builder->executeQuery();

        // If we have no data just return null.
        if (!$statement->rowCount()) {
            return null;
        }

        if (false === $result = $statement->fetchAssociative()) {
            return null;
        }

        // Build a new container.
        $container = new Container();
        $container->setLatitude($result['geo_lat']);
        $container->setLongitude($result['geo_long']);
        $container->setSearchParam(
            \strtr(
                $builder->getSQL(),
                [
                    ':search'  => $this->connection->quote($address),
                    ':country' => $this->connection->quote($country)
                ]
            )
        );

        return $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeSettingNames()
    {
        return \array_merge(
            parent::getAttributeSettingNames(),
            [
                'mandatory',
                'filterable',
                'searchable',
                'get_geo',
                'countrymode',
                'country_preset',
                'country_get',
                'lookupservice',
                'datamode',
                'single_attr_id',
                'first_attr_id',
                'second_attr_id'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDefinition($arrOverrides = [])
    {
        $arrFieldDef                     = parent::getFieldDefinition($arrOverrides);
        $arrFieldDef['inputType']        = 'text';
        $arrFieldDef['eval']['readonly'] = true;

        return $arrFieldDef;
    }

    /**
     * This method is called to store the data for certain items to the database.
     *
     * @param mixed[] $arrValues The values to be stored into database. Mapping is item id=>value.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setDataFor($arrValues)
    {
        // No-op.
    }

    /**
     * Retrieve the filter options of this attribute.
     *
     * Retrieve values for use in filter options, that will be understood by DC_ filter
     * panels and frontend filter select boxes.
     * One can influence the amount of returned entries with the two parameters.
     * For the id list, the value "null" represents (as everywhere in MetaModels) all entries.
     * An empty array will return no entries at all.
     * The parameter "used only" determines, if only really attached values shall be returned.
     * This is only relevant, when using "null" as id list for attributes that have pre configured
     * values like select lists and tags i.e.
     *
     * @param string[]|null $idList   The ids of items that the values shall be fetched from
     *                                (If empty or null, all items).
     * @param bool          $usedOnly Determines if only "used" values shall be returned.
     * @param array|null    $arrCount Array for the counted values.
     *
     * @return array All options matching the given conditions as name => value.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getFilterOptions($idList, $usedOnly, &$arrCount = null)
    {
        return [];
    }

    /**
     * This method is called to retrieve the data for certain items from the database.
     *
     * @param string[] $arrIds The ids of the items to retrieve.
     *
     * @return mixed[] The nature of the resulting array is a mapping from id => "native data" where
     *                 the definition of "native data" is only of relevance to the given item.
     */
    public function getDataFor($arrIds)
    {
        if (!array_key_exists($this->get('id'), self::$data)) {
            try {
                $this->runGeodistance($arrIds, 'ASC');
            } catch (\Exception $e) {
                self::$data[$this->get('id')] = [];
            }
        }

        $return = [];
        foreach ($arrIds as $id) {
            if (isset(self::$data[$this->get('id')][$id])) {
                $return[$id] = self::$data[$this->get('id')][$id];
            } else {
                $return[$id] = -1;
            }
        }

        return $return;
    }

    /**
     * Remove values for items.
     *
     * @param string[] $arrIds The ids of the items to retrieve.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function unsetDataFor($arrIds)
    {
        // No-op.
    }

    /**
     * Try to get a valid country information.
     *
     * @return string|null The country short tag (2-letters) or null.
     */
    private function getCountryInformation()
    {
        // Get the country for the lookup.
        $country = null;

        if (('get' === $this->get('countrymode')) && $this->get('country_get')) {
            $getValue = $this->input->get($this->get('country_get')) ?: $this->input->post($this->get('country_get'));
            $getValue = \trim($getValue);
            if (!empty($getValue)) {
                $country = $getValue;
            }
        } elseif ('preset' === $this->get('countrymode')) {
            $country = $this->get('country_preset');
        }

        return $country;
    }
}
