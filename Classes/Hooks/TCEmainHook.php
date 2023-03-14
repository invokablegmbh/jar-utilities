<?php

declare(strict_types=1);

namespace Jar\Utilities\Hooks;

/*
 * This file is part of the Jar/Feditor project.
 */

use Doctrine\DBAL\DBALException;
use InvalidArgumentException;
use Doctrine\DBAL\Query\QueryException;
use Jar\Feditor\Core\Field;
use Jar\Feditor\Core\FrontendContainer;
use ReflectionException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\TooDirtyException;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use UnexpectedValueException;

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

    /**  
     * @param ConnectionPool $connectionPool 
     * @return void 
     */
    public function __construct(ConnectionPool $connectionPool)
    {        
        $this->connectionPool = $connectionPool;
        $this->initReverseRelationTca();
    }

    /**
     * Represents a child-to-parent relation.
     */
    protected function initReverseRelationTca(): void {
        $this->reverseRelationTca = [];

        if(!is_array($GLOBALS['TCA'])) {
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

                if(
                    !array_key_exists('type', $config)
                    // check for invalid relation types                    
                    || !in_array(strtolower($config['type'] ?? ''), ['inline', 'select', 'group', 'category'])
                    // check for invalid relation tables
                    || ($config['type'] === 'group' && (
                        !array_key_exists('allowed', $config) || (array_key_exists('internal_type', $config) && $config['internal_type'] !== 'db')
                    ))
                    || ($config['type'] !== 'group' && empty($config['foreign_table']))
                    // don't handle language fields
                    || (isset($tca['ctrl']['transOrigPointerField']) && $tca['ctrl']['transOrigPointerField'] === $column)
                ){
                    continue;
                }

                $foreignTable = ($config['type'] === 'group') ? $config['allowed'] : $config['foreign_table'];

                // create entry for foreign table if not exists
                if(!array_key_exists($foreignTable, $this->reverseRelationTca)) {
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
     * @param string $table 
     * @param int $uid 
     * @return void 
     */
    protected function updateTimestamp(string $table, int $uid): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder
            ->update($table)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
            )
            ->set('tstamp', time())
            ->execute();
    }


    /**
     * @param string $initiatorTable 
     * @param int $uid 
     * @return void 
     */
    protected function updateParentTableTimestamp(string $initiatorTable, int $uid): void
    {
        $ct = $this->frontendContainer->getCustomTypeByTableName($initiatorTable);
        if (!empty($ct)) {
            $parentField = $ct->getParentContainer();
            $parentCustomType = $ct->getParentCustomType();

            if (!empty($parentCustomType) && !empty($parentField) && get_class($parentField) === Field::class) {
                $parentTablename = $parentCustomType->getTableName();
                $foreignField = $GLOBALS['TCA'][$parentTablename]['columns'][$parentField->getColumnName()]['config']['foreign_field'];
                if (!empty($foreignField)) {
                    $queryBuilder = $this->connectionPool->getQueryBuilderForTable($initiatorTable);
                    $parentUid = reset($queryBuilder
                        ->select($foreignField)
                        ->from($initiatorTable)
                        ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
                        ->execute()
                        ->fetchAll())[$foreignField];
                    if (!empty($parentUid)) {
                        $itemKey = $parentTablename . '-' . $parentUid;
                        // Check if we updated this row allready (recursion protection)
                        if (!in_array($itemKey, $this->updatedItemsBuffer)) {
                            $this->updatedItemsBuffer[] = $itemKey;
                            $this->updateTimestamp($parentTablename, (int) $parentUid);
                            $this->updateParentTableTimestamp($parentTablename, (int) $parentUid);
                        }
                    }
                }
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

            // get reverse relations
            $reverseRelations = $this->reverseRelationTca[$table]['parentRelations'] ?? [];

            DebuggerUtility::var_dump($reverseRelations);
            die();

            if ($table == 'tt_content') {
                $this->updateTimestamp('tt_content', $id);
            } else {
                $initiatorTable = end(array_keys($pObj->datamap));
                $uid = reset(array_keys(end($pObj->datamap)));

                $this->updatedItemsBuffer = [$initiatorTable . '-' . $uid];
                $this->updateParentTableTimestamp($initiatorTable, (int) $uid);
            }
        }
    }
}
