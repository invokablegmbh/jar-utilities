<?php

declare(strict_types=1);

namespace Jar\Utilities\Hooks;

/*
 * This file is part of the Jar/Feditor project.
 */

use Doctrine\DBAL\DBALException;
use InvalidArgumentException;
use Doctrine\DBAL\Query\QueryException;
use Jar\Utilities\Utilities\IteratorUtility;
use ReflectionException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\TooDirtyException;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use UnexpectedValueException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 *
 * @author Isaac "DaPedro" Hintenjubel <ih@jcdn.de>
 * @package JAR.Feditor
 * @subpackage ViewHelpers
 */

class TCEmainHook implements SingletonInterface
{
    protected ConnectionPool $connectionPool;
    protected array $updatedItemsBuffer = [];
    protected array $reverseRelationTca = [];
    // when bad things happen, this will prevent an endless loop
    protected $securityIterator = 0;

    /**  
     * @param ConnectionPool $connectionPool 
     * @return void 
     */
    public function __construct(ConnectionPool $connectionPool)
    {
        $this->connectionPool = $connectionPool;
        $this->initReverseRelationTca();
        //$this->testRelations();
    }

    /**
     * Represents a child-to-parent relation.
     */
    protected function initReverseRelationTca(): void
    {
        $this->reverseRelationTca = [];

        if (!is_array($GLOBALS['TCA'])) {
            return;
        }

        foreach ($GLOBALS['TCA'] as $table => $tca) {

            // check for tables with valid tstamps
            if (!array_key_exists('ctrl', $tca) || !array_key_exists('tstamp', $tca['ctrl'])) {
                continue;
            }

            foreach ($tca['columns'] ?? [] as $column => $columnConfig) {
                if (!is_array($columnConfig['config'])) {
                    continue;
                }

                $config = $columnConfig['config'];

                if (
                    !array_key_exists('type', $config)
                    // check for invalid relation types                    
                    || !in_array(strtolower($config['type'] ?? ''), ['inline', 'select', 'group']) // , 'category' - ignore it for now
                    // check for invalid relation tables
                    || ($config['type'] === 'group' && (!array_key_exists('allowed', $config) || (array_key_exists('internal_type', $config) && $config['internal_type'] !== 'db')
                    ))
                    || ($config['type'] !== 'group' && empty($config['foreign_table']))
                    // don't handle language fields
                    || (isset($tca['ctrl']['transOrigPointerField']) && $tca['ctrl']['transOrigPointerField'] === $column)
                ) {
                    continue;
                }

                $foreignTable = ($config['type'] === 'group') ? $config['allowed'] : $config['foreign_table'];

                // create entry for foreign table if not exists
                if (!array_key_exists($foreignTable, $this->reverseRelationTca)) {
                    // just work with foreign tables which have a tstamp field
                    if (!array_key_exists($foreignTable, $GLOBALS['TCA']) || !array_key_exists('tstamp', $GLOBALS['TCA'][$foreignTable]['ctrl'])) {
                        continue;
                    }
                    $this->reverseRelationTca[$foreignTable] = [
                        'parentRelations' => []
                    ];
                }

                // create entry for parent table relation
                $this->reverseRelationTca[$foreignTable]['parentRelations'][] = [
                    'table' => $table,
                    'field' => $column,
                    'tstampColumn' => $tca['ctrl']['tstamp'],
                    'tcaconfig' => $config
                ];
            }
        }
    }




    /**
     * @param string $status 
     * @param string $table 
     * @param string $id 
     * @param array $fieldArray 
     * @param DataHandler $pObj 
     * @return void 
     * @throws UnexpectedValueException 
     * @throws DBALException 
     * @throws InvalidArgumentException 
     * @throws QueryException 
     * @throws TooDirtyException 
     * @throws ReflectionException 
     */
    public function processDatamap_afterDatabaseOperations(string $status, string $table, string $id, array $fieldArray, \TYPO3\CMS\Core\DataHandling\DataHandler &$pObj)
    {

        $id = (int) $id;
        // General Update when DB Queries are performed (Update Parent when Inline Elements also Change - triggers Rebuild of their output Cache)
        if ($status == 'update') {
            $this->updateParents($table, [$id]);
        }
    }

    /**
     * Iterate throught all parents and update their timestamps
     */

    protected function updateParents(string $table, array $uids)
    {

        // remove all uids which are already in the buffer
        foreach ($uids as $key => $uid) {
            $checkKey = $table . '-' . $uid;
            if (array_key_exists($checkKey, $this->updatedItemsBuffer)) {
                unset($uids[$key]);
            } else {
                $this->updatedItemsBuffer[$checkKey] = true;
            }
        }
        // get reverse relations
        $reverseRelations = $this->reverseRelationTca[$table]['parentRelations'] ?? [];

        $uidChunks = array_chunk($uids, 512);

        foreach ($uidChunks as $uidChunk) {


            foreach ($reverseRelations as $reverseRelation) {
                $tcaConfig = $reverseRelation['tcaconfig'];


                $queryBuilder = $this->connectionPool->getQueryBuilderForTable($reverseRelation['table']);

                $parentElements = [];

                // foreign table relations
                if (array_key_exists('foreign_table', $tcaConfig) && array_key_exists('foreign_field', $tcaConfig)) {

                    $foreignConditions = [
                        $queryBuilder->expr()->in('ft.uid', $queryBuilder->createNamedParameter($uidChunk, Connection::PARAM_INT_ARRAY))
                    ];

                    if (array_key_exists('foreign_match_fields', $tcaConfig)) {
                        foreach ($tcaConfig['foreign_match_fields'] as $column => $value) {
                            $foreignConditions[] = $queryBuilder->expr()->eq('ft.' . $column, $queryBuilder->createNamedParameter($value, \PDO::PARAM_STR));
                        }
                    }

                    if (array_key_exists('foreign_table_field', $tcaConfig)) {
                        $foreignConditions[] = $queryBuilder->expr()->eq('ft.' . $tcaConfig['foreign_table_field'], $queryBuilder->createNamedParameter($reverseRelation['table'], \PDO::PARAM_STR));
                    }

                    if (array_key_exists('foreign_selector', $tcaConfig)) {
                        $foreignConditions[] = $queryBuilder->expr()->eq('ft.' . $tcaConfig['foreign_selector'], $queryBuilder->quoteIdentifier($reverseRelation['table'] . '.uid'));
                    }

                    // get parent elements    
                    $parentElements = $queryBuilder
                        ->select($reverseRelation['table'] . '.uid')
                        ->from($reverseRelation['table'])
                        ->join(
                            $reverseRelation['table'],
                            $tcaConfig['foreign_table'],
                            'ft',
                            $queryBuilder->expr()->eq('ft.' . $tcaConfig['foreign_field'], $queryBuilder->quoteIdentifier($reverseRelation['table'] . '.uid'))
                        )
                        ->where(
                            $queryBuilder->expr()->andX(
                                ...$foreignConditions
                            )
                    );
                    if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() < 11) {
                        $parentElements = $queryBuilder->execute()
                        ->fetchAllAssociative();
                    } else {
                        $parentElements = $queryBuilder->executeQuery()
                        ->fetchAll();
                    }
                }

                // Resolve Basic mm relations
                else if (isset($tcaConfig['MM']) && !empty($tcaConfig['MM']) && !array_key_exists('MM_opposite_field', $tcaConfig) && !array_key_exists('MM_hasUidField', $tcaConfig) && !array_key_exists('MM_match_fields', $tcaConfig)) {
                    // get parent elements from mm table
                    // we keep it simple, just use that for simple mm relations check if mm table has 'uid_local' and 'uid_foreign' fields                    
                    // TODO: Handle $tcaConfig['MM_match_fields']

                    $mmTableColumns = $this->connectionPool->getConnectionForTable($tcaConfig['MM'])->getSchemaManager()->listTableColumns($tcaConfig['MM']);
                    if (!array_key_exists('uid_local', $mmTableColumns) || !array_key_exists('uid_foreign', $mmTableColumns)) {
                        continue;
                    }

                    // get parent elements                    
                    $parentElements = $queryBuilder
                        ->select($reverseRelation['table'] . '.uid')
                        ->from($reverseRelation['table'])
                        ->join(
                            $reverseRelation['table'],
                            $tcaConfig['MM'],
                            'mm',
                            $queryBuilder->expr()->eq('mm.uid_local', $queryBuilder->quoteIdentifier($reverseRelation['table'] . '.uid'))
                        )
                        ->where(
                            $queryBuilder->expr()->in('mm.uid_foreign', $queryBuilder->createNamedParameter($uidChunk, Connection::PARAM_INT_ARRAY))
                        );
                    if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() < 11) {
                        $parentElements = $queryBuilder->execute()
                        ->fetchAllAssociative();
                    } else {
                        $parentElements = $queryBuilder->executeQuery()
                        ->fetchAll();
                    }   
                }

                // resolve simple foreign relations // resolve simple group relations // basic selects without mm relations
                else if (array_key_exists('foreign_table', $tcaConfig) || $reverseRelation['tcaconfig']['type'] === 'group' || $reverseRelation['tcaconfig']['type'] === 'select') {

                    $wheres = [];
                    foreach ($uidChunk as $uid) {
                        $wheres[] = $queryBuilder->expr()->inSet($reverseRelation['field'], $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT));
                    }

                    // get parent elements
                    $parentElements = $queryBuilder
                        ->select($reverseRelation['table'] . '.uid')
                        ->from($reverseRelation['table'])
                        ->where(
                            ...$wheres
                        );
                    if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() < 11) {
                        $parentElements = $queryBuilder->execute()
                        ->fetchAllAssociative();
                    } else {
                        $parentElements = $queryBuilder->executeQuery()
                        ->fetchAll();
                    } 
                }

                // update timestamps
                $parentUids = IteratorUtility::pluck($parentElements, 'uid');
                if (!empty($parentUids)) {
                    $queryBuilder
                        ->update($reverseRelation['table'])
                        ->where(
                            $queryBuilder->expr()->in(
                                'uid',
                                $queryBuilder->createNamedParameter($parentUids, Connection::PARAM_INT_ARRAY)
                            )
                        )
                        ->set($reverseRelation['tstampColumn'], time());
                    if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() < 11) {
                        $parentElements = $queryBuilder->execute();
                    } else {
                        $parentElements = $queryBuilder->executeStatement();
                    }  

                    if ($this->securityIterator > 10000) {
                        break;
                    }

                    $this->securityIterator++;

                    $this->updateParents($reverseRelation['table'], $parentUids);
                }
            }
        }
    }


    /**
     * Tester method for unhandled types of relations
     */

    public function testRelations()
    {
        foreach ($this->reverseRelationTca as $table => $relations) {
            foreach ($relations['parentRelations'] as $relation) {
                $tcaConfig = $relation['tcaconfig'];
                // plain mm relations without additional configuration 
                if (isset($tcaConfig['MM']) && !empty($tcaConfig['MM']) && !array_key_exists('MM_opposite_field', $tcaConfig) && !array_key_exists('MM_hasUidField', $tcaConfig) && !array_key_exists('MM_match_fields', $tcaConfig)) {
                    continue;
                }

                // resolve simple group relations
                if ($tcaConfig['type'] === 'group') {
                    continue;
                }

                // basic selects without mm relations
                if ($tcaConfig['type'] === 'select') {
                    continue;
                }

                // foreign table relations
                if (array_key_exists('foreign_table', $tcaConfig) && array_key_exists('foreign_field', $tcaConfig)) {
                    continue;
                }

                // resolve simple foreign relations
                if (array_key_exists('foreign_table', $tcaConfig)) {
                    continue;
                }

                DebuggerUtility::var_dump($relation, $table);
            }
        }
    }
}
