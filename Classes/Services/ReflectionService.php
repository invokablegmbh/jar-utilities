<?php

declare(strict_types=1);

namespace Jar\Utilities\Services;

use InvalidArgumentException;
use RuntimeException;
use ReflectionException;
use Jar\Utilities\Utilities\FileUtility;
use Jar\Utilities\Utilities\FormatUtility;
use Jar\Utilities\Utilities\FrontendUtility;
use Jar\Utilities\Utilities\IteratorUtility;
use Jar\Utilities\Utilities\TcaUtility;
use Jar\Utilities\Utilities\WildcardUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\TooDirtyException;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */


/**
 * @package Jar\Utilities\Services
 * Service class for converting complex objects to a simple array structure based of TCA configuration.
 * Handy for faster "backend to frontend" development, headless systems and ajax calls.
 **/

class ReflectionService
{
	/**
	 * Internal Cache for processed Elements also serves for recursion protection
	 * @var array
	 */
	private array $elementStorage = [];

	/**
	 * Configration for building array like building images (max width, max height ..)
	 * currently just the key "file" is in use
	 * @var array
	 */
	private array $buildingConfiguration = [];

	/**
	 * List of columns that are generally not processed
	 * This blacklist will be applied to every table.
	 * You can use wildcards like "?" or "*".
	 * @var array
	 */
	private array $columnBlacklist = [
		't3ver_*', 'l18n_*', 'l10n_*', 'tx_flux_*', 'crdate', 'cruser_id', 'editlock', 'hidden', 'sorting', 'CType', 'rowDescription', 'deleted', 'starttime',
		'endtime', 'colPos', 'sectionIndex', 'sys_language_uid', 'tx_impexp_origuid', 'tx_flux_migrated_version', 'tx_flux_parent', 'fe_group', 'linkToTop'
	];

	/**
	 * List of table specific columns which aren't processed
	 * Wildcards like * or ? are allowed
	 * @var array
	 */
	private array $tableColumnBlacklist = [
		'sys_category' => ['parent', 'items', 'tx_kesearch_*'],
	];

	/**
	 * List of tables columns which should be processed exclusively
	 * Wildcards like * or ? are allowed
	 * @var array
	 */
	private array $tableColumnWhitelist = [];


	/**
	 * Replacement for Column names
	 * f.e. 'tt_content' => ['table_'] converts 'tt_content->table_caption' to 'tt_content->caption'
	 * @var array
	 */
	private array $tableColumnRemoveablePrefixes = [];

	/**
	 * Array for used TCA field definitions, helpful for Post-handling that prepared data
	 * @var array
	 */
	private array $tcaFieldDefinition = [];

	/**
	 * Flag for Debug Mode
	 * @var bool
	 */
	private bool $debug = false;

	/**
	 * Remap Columnnames in reflected result.
	 * f.e. 'tt_content' => ['table_caption' => 'heading'] converts 'tt_content->table_caption' to 'tt_content->heading'
	 * Important: takes action AFTER replacement of ColumnNames! Keep that in mind.
	 * @var array
	 */
	private array $tableColumnRemapping = [];

	/*
		Related items  structure:
		'table' => [
			id => [
				'sys_language_uid' => 0
			]
			...
		]
		...
	*/
	/**
	 * Tablebased list of related items for collection loads of relations
	 *
	 * @var array
	 */
	private array $relatedItems = [];

	/**
	 * Tablebased list of unloaded related items for collection loads of relations
	 *
	 * @var array
	 */
	private array $unloadedRelatedItems = [];

	/**
	 * Tablebased list of loaded related items for collection loads of relations
	 *
	 * @var array
	 */
	private array $loadedRelatedItems = [];

	/*
		Related children structure:
		'sys_language_uid' => [
			'table' => [
				'foreign_field' => [
					parent_id => [],
					...
				]
				...
			]
			...
		]
		...
	*/

	/**
	 * List of php small scale hooks after a element is reflected
	 * @var array
	 */
	private array $fieldFinisherMethods = [];

	/**
	 * List of php small scale hooks after a relation is reflected
	 * @var array
	 */
	private array $relationFinisherMethods = [];

	/**
	 * Tablebased list of related children for collection loads of relations
	 *
	 * @var array
	 */
	private array $relatedChildren = [];

	/**
	 * Tablebased list of unloaded children items for collection loads of relations
	 *
	 * @var array
	 */
	private array $unloadedRelatedChildren = [];

	/**
	 * Tablebased list of loaded children items for collection loads of relations
	 *
	 * @var array
	 */
	private array $loadedRelatedChildren = [];

	/**
	 * Enables result cache of buildArrayByRows
	 *
	 * @var bool
	 */
	private bool $enableCache = true;

	/**
	 * Just load basic fields of related childrens
	 *
	 * @var bool
	 */
	private bool $fetchBasicRelationFields = false;

	/**
	 * Reflects a list of record rows.
	 *
	 * @param array $rows The record list.
	 * @param string $table The tablename.
	 * @param int $maxDepth The maximum depth at which related elements are loaded (default is 8).
	 * @param bool $resolveRelations Flag for resolving subrelations
	 * @return array Reflected result.
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 * @throws TooDirtyException
	 * @throws ReflectionException
	 */
	public function buildArrayByRows(array $rows, string $table, int $maxDepth = 8, bool $resolveRelations = false): array
	{
		if($this->enableCache) {
			// create a hash for rows, table and maxDepth and all current settings		
			$identifier = md5(
				serialize($rows) .
					$table .
					$maxDepth .
					serialize($this->buildingConfiguration) .
					serialize($this->columnBlacklist) .
					serialize($this->tableColumnBlacklist) .
					serialize($this->tableColumnWhitelist) .
					serialize($this->tableColumnRemoveablePrefixes) .
					serialize($this->tableColumnRemapping) .
					serialize($this->fieldFinisherMethods) .
					serialize($this->relatedItems) .
					serialize($this->relatedChildren) .
					GeneralUtility::_GP('frontend_editing')
			);
			$cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('jar_utilities_reflection');
		}
		if (!$this->enableCache || ($result = $cache->get($identifier)) === false) {
			$result = [];
			foreach ($rows as $key => $row) {
				$result[$key] = $this->buildArrayByRow($row, $table, $maxDepth, $resolveRelations);
			}

			// handle collection load of relations
			if(!$resolveRelations) {
				while (count($this->unloadedRelatedItems) + count($this->unloadedRelatedChildren)) {
					$this->collectUnloadedElements();
				}
			}

			if ($this->enableCache) {
				$cache->set($identifier, $result);
			}
		}

		return $result;
	}


	/**
	 * Reflects a single record row.
	 *
	 * @param array $row The record row.
	 * @param string $table The tablename.
	 * @param int $maxDepth The maximum depth at which related elements are loaded (default is 8).
	 * @param bool $resolveRelations Flag for resolving subrelations
	 * @return null|array
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 * @throws TooDirtyException
	 * @throws ReflectionException
	 */
	public function &buildArrayByRow(array $row, string $table, int $maxDepth = 8, bool $resolveRelations = true): ?array
	{
		if ($this->debug) {
			DebuggerUtility::var_dump($row, 'Reflection Service buildArrayByRow IN ' . $table);
		}

		if ($maxDepth <= 0) {
			return null;
		}

		$result = [];
		$result['uid'] = $uid = $row['uid'];

		if (empty($row)) {
			return [];
		}

		// Return referende from storage if element allready was populated
		if ($this->isInStorage($table, $uid)) {
			return $this->elementStorage[$table][$uid];
		}
		// put something empty in storage, to mark it as "exist", but not filled yet
		$this->storeItemInStorage($table, $uid, null);

		// get relevant columns for that type from TCA
		$tcaColumns = TcaUtility::getColumnsByRow($table, $row);

		$whitelist = $this->tableColumnWhitelist[$table] ?? [];
		$blacklist = array_merge($this->columnBlacklist ?? [], $this->tableColumnBlacklist[$table] ?? []);
		$removeablePrefixes = $this->tableColumnRemoveablePrefixes[$table] ?? [];
		$columnRemapping = $this->tableColumnRemapping[$table] ?? [];

		$tcaType = TcaUtility::getTypeFromRow($table, $row);

		// load TCA Config for each column and proccess
		foreach ($tcaColumns as $tcaColumn) {
			// just process whitelist columns (if set)
			if (Count($whitelist) && !WildcardUtility::matchAgainstPatternList($whitelist, $tcaColumn)) {
				continue;
			}

			// just handle columns which aren't generally blacklisted or blacklisted for that table
			if (in_array($tcaColumn, $blacklist) || WildcardUtility::matchAgainstPatternList($blacklist, $tcaColumn)) {
				continue;
			}

			$tcaDefinition = TcaUtility::getFieldDefinition($table, $tcaColumn, $tcaType);
			$config = $tcaDefinition['config'] ?? [];

			// final key name in result list
			if (empty($removeablePrefixes)) {
				$targetKey = $tcaColumn;
			} else {
				$targetKey = str_replace($removeablePrefixes, '', $tcaColumn);
			}
			if (!empty($columnRemapping) && key_exists($targetKey, $columnRemapping)) {
				$targetKey = $columnRemapping[$targetKey];
			}

			// Store the TCA informations for post handling
			if (!key_exists($table, $this->tcaFieldDefinition)) {
				$this->tcaFieldDefinition[$table] = [];
			}
			$this->tcaFieldDefinition[$table][$targetKey] = $tcaDefinition;

			// bloody fallback when no TCA informations are found
			if (empty($config)) {
				$result[$targetKey] = $row[$tcaColumn] ?? [];
			} else {
				// populate the raw row informations (unless it is a password field)
				if (!empty($config['eval'])) {
					if (strpos($config['eval'], 'password') !== false) {
						continue;
					}
				}

				$rawValue = $row[$tcaColumn] ?? '';

				switch ($config['type']) {
					case 'input':
						switch ($config['renderType'] ?? '') {
							case 'inputLink':
								// Links
								$result[$targetKey] = FormatUtility::buildLinkArray($rawValue);
								break;
							case 'inputDateTime':
								// Date, Time and DateTime Fields
								$eval = GeneralUtility::trimExplode(',', strtolower($config['eval']));
								if (in_array('time', $eval)) {
									$result[$targetKey] = FormatUtility::buildTimeArray($rawValue);
								} else  if (in_array('datetime', $eval) || in_array('date', $eval)) {
									$result[$targetKey] = FormatUtility::buildDateTimeArrayFromString((string) $rawValue);
								} else {
									$result[$targetKey] = $rawValue;
								}
								break;
							default:
								$result[$targetKey] = $rawValue;
						}
						break;

					case 'text':
						// Textareas and RTE: add <br> to textareas, parse the content for RTE Text
						if (!array_key_exists('enableRichtext', $config)) {
							$result[$targetKey] = nl2br($rawValue ?? '');
						} else {
							if (!empty($rawValue)) {
								$result[$targetKey] = FormatUtility::renderRteContent($rawValue);
							} else {
								$result[$targetKey] = $rawValue;
							}
						}
						break;

					case 'check':
						// Checkboxes
						$result[$targetKey] = (bool) $rawValue;
						break;

					case 'radio':
					case 'passthrough':
					case 'slug':
						$result[$targetKey] = $rawValue;
						break;

					case 'inline':
					case 'select':
					case 'group':
					case 'category':

						// just return the raw value(s) of flat selects or group which aren't handle db-relations
						if (
							($config['type'] === 'group' && (!array_key_exists('allowed', $config) || (array_key_exists('internal_type', $config) && $config['internal_type'] !== 'db'))) ||
							($config['type'] !== 'group' && empty($config['foreign_table']))
						) {
							$result[$targetKey] = ((int) ($config['maxitems'] ?? 0) > 1) ? GeneralUtility::trimExplode(',', $rawValue, true) : $rawValue;
							break;
						}



						$foreignTable = ($config['type'] === 'group') ? $config['allowed'] : $config['foreign_table'];

						// handle sys_file_references directly, no recursive resolving
						if (array_key_exists('foreign_table', $config) && $config['foreign_table'] === 'sys_file_reference') {
							$relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
							$relationHandler->start($rawValue, $foreignTable, $config['MM'] ?? '', $uid, $table, $config);
							$relationHandler->getFromDB();
							$resolvedItemArray = $relationHandler->getResolvedItemArray();

							$result[$targetKey] = [];
							foreach ($resolvedItemArray as $resolvedItem) {
								if (!empty($resolvedItem['uid'])) {
									$fileBuidlingConfiguration = $this->buildingConfiguration['file'] ?? [];
									$cropVariants = $config['overrideChildTca']['columns']['crop']['config']['cropVariants'] ?? [];

									// fallback to default cropVariants
									$defaultCropVariants = $GLOBALS['TCA']['sys_file_reference']['columns']['crop']['config']['cropVariants'] ?? [];
									ArrayUtility::mergeRecursiveWithOverrule($cropVariants, $defaultCropVariants);
									if(empty($cropVariants)) {									
										$cropVariants = [
											'default' => []
										];
									}

									ArrayUtility::mergeRecursiveWithOverrule($fileBuidlingConfiguration, [
										'tcaCropVariants' => $cropVariants
									]);

									$fileArray = FileUtility::buildFileArrayBySysFileReferenceUid($resolvedItem['uid'], $fileBuidlingConfiguration);

									if (!empty($fileArray)) {
										$result[$targetKey][] = $fileArray;
									}
								}
							}
							break;
						}

						// set currentLanguage to the language of the row
						$currentLanguageUid = $row['sys_language_uid'] ?? 0;

						// Load relations to other tables
						if (!$resolveRelations) {						

							// resolve collected parent / child relations
							if (!array_key_exists('MM', $config) && $foreignTable && array_key_exists('foreign_field', $config)) {

								// switch to the real UID for translated elements
								if ($currentLanguageUid !== 0) {
									$uid = $row['_LOCALIZED_UID'] ?? $row['_PAGES_OVERLAY_UID'] ?? $uid;
								}

								$foreignField = $config['foreign_field'];
								$foreignSorting = $config['foreign_sortby'] ?? '';

								if (is_array($this->loadedRelatedChildren[$currentLanguageUid][$foreignTable][$foreignField][$foreignSorting][$uid] ?? null)) {
									// is allready loaded?
									$this->relatedChildren[$currentLanguageUid][$foreignTable][$foreignField][$foreignSorting][$uid] = &$this->loadedRelatedChildren[$currentLanguageUid][$foreignTable][$foreignField][$foreignSorting][$uid];
								} else {
									// mark for collection loading
									$this->unloadedRelatedChildren[$currentLanguageUid][$foreignTable][$foreignField][$foreignSorting][$uid] = [
										'config' => $config,
									];
									$this->relatedChildren[$currentLanguageUid][$foreignTable][$foreignField][$foreignSorting][$uid] = &$this->unloadedRelatedChildren[$currentLanguageUid][$foreignTable][$foreignField][$foreignSorting][$uid];
								}

								$result[$targetKey] = &$this->relatedChildren[$currentLanguageUid][$foreignTable][$foreignField][$foreignSorting][$uid];
							} else {
								// UID based mode
								$relationHandler = GeneralUtility::makeInstance(RelationHandler::class);								
								$relationHandler->start($rawValue, $foreignTable, $config['MM'] ?? '', $uid, $table, $config);
								$relationList = [];
								foreach ($relationHandler->itemArray as $item) {
									$foreignRelationTable = $item['table'];
									$foreignRelationId = $item['id'];

									// just set markers to relations
									if (!key_exists($foreignRelationTable, $this->relatedItems)) {
										$this->relatedItems[$foreignRelationTable] = [];
									}
									if (!key_exists($foreignRelationTable, $this->unloadedRelatedItems)) {
										$this->unloadedRelatedItems[$foreignRelationTable] = [];
									}
									if (!key_exists($foreignRelationTable, $this->loadedRelatedItems)) {
										$this->loadedRelatedItems[$foreignRelationTable] = [];
									}
									if (!key_exists($currentLanguageUid, $this->relatedItems[$foreignRelationTable])) {
										$this->relatedItems[$foreignRelationTable][$currentLanguageUid] = [];
									}
									if (!key_exists($currentLanguageUid, $this->unloadedRelatedItems[$foreignRelationTable])) {
										$this->unloadedRelatedItems[$foreignRelationTable][$currentLanguageUid] = [];
									}
									if (!key_exists($currentLanguageUid, $this->loadedRelatedItems[$foreignRelationTable])) {
										$this->loadedRelatedItems[$foreignRelationTable][$currentLanguageUid] = [];
									}


									if (!empty($this->loadedRelatedItems[$foreignRelationTable][$currentLanguageUid][$foreignRelationId])) {
										// is allready loaded?
										$this->relatedItems[$foreignRelationTable][$currentLanguageUid][$foreignRelationId] = &$this->loadedRelatedItems[$foreignRelationTable][$currentLanguageUid][$foreignRelationId];
									} else {
										// mark for collection loading
										$this->unloadedRelatedItems[$foreignRelationTable][$currentLanguageUid][$foreignRelationId] = true;
										$this->relatedItems[$foreignRelationTable][$currentLanguageUid][$foreignRelationId] = &$this->unloadedRelatedItems[$foreignRelationTable][$currentLanguageUid][$foreignRelationId];
									}

									$relationList[] = &$this->relatedItems[$foreignRelationTable][$currentLanguageUid][$foreignRelationId];
								}
								$result[$targetKey] = $relationList;
							}
						} else {
							$relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
							$relationHandler->start($rawValue, $foreignTable, $config['MM'] ?? '', $uid, $table, $config);
							// thus the relation handler works only with elements in the default language, just use in that case,
							// otherwise load the elements on our own
							if ($currentLanguageUid === 0) {
								$relationHandler->setFetchAllFields(true);
							}

							// don't fetch hidden and deleted items
							$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($foreignTable)->createQueryBuilder();
							$relationHandler->additionalWhere[$foreignTable] = $queryBuilder->expr()->eq('hidden', 0);
							$relationHandler->additionalWhere[$foreignTable] = $queryBuilder->expr()->eq('deleted', 0);

							// we have to load the elements twice, first (here) in default and later as translated value
							if($this->fetchBasicRelationFields) {								
								$relationHandler->setFetchAllFields(false);
							}
							$dbResult = $relationHandler->getFromDB();

							$resolvedItemArray = $relationHandler->getResolvedItemArray();

							if (empty($resolvedItemArray)) {
								$result[$targetKey] = [];
								break;
							} else {

								// When we have just sub-relations from one other table, make huge select, otherwise load row for row
								$foreignTables = IteratorUtility::pluck($resolvedItemArray, 'table');
								$foreignTableAmount = Count(array_unique($foreignTables));

								if ($foreignTableAmount === 1) {
									$foreignTable = reset($foreignTables);
									$selectUids = IteratorUtility::pluck($resolvedItemArray, 'uid');

									$foreignItems = [];

									if ($currentLanguageUid === 0) {
										// default language, loaded via relation handler
										foreach ($resolvedItemArray as $resolvedItem) {
											$foreignRow = $resolvedItem['record'] ?? $dbResult[$resolvedItem['table']][$resolvedItem['uid']];
											$sortUid = array_search($foreignRow['uid'], $selectUids);
											$foreignItems[$sortUid] = &$this->buildArrayByRow($foreignRow, $foreignTable, $maxDepth - 1);
										}
									} else {
										// load translated elements on our own									
										$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
										$queryBuilder = $connectionPool->getQueryBuilderForTable($foreignTable);

										$fields = $this->fetchBasicRelationFields ? ['uid'] : ['*'];

										$queryResult = $queryBuilder
											->select(...$fields)
											->from($foreignTable)
											->where(
												$this->createLanguageContraints($queryBuilder, $selectUids, $foreignTable, $currentLanguageUid)
											)
											->execute();
										while ($foreignRow = $queryResult->fetch()) {
											$sortUid = array_search($foreignRow['uid'], $selectUids);
											$foreignItems[$sortUid] = &$this->buildArrayByRow($foreignRow, $foreignTable, $maxDepth - 1);
										}
									}

									ksort($foreignItems);

									$params = [
										'foreignTable' => $foreignTable,
										'tcaConfig' => $config,
									];
									foreach ($this->relationFinisherMethods as $finisherMethod) {
										$params['items'] = $foreignItems;
										$foreignItems = GeneralUtility::callUserFunction($finisherMethod, $params, $this);
									}

									$result[$targetKey] = $foreignItems;
								} else {
									// TODO: Clean this up, or delete it
									$foreignItems = [];
									foreach ($resolvedItemArray as $resolvedItem) {
										if ($foreignRow = $resolvedItem['record'] ?? $dbResult[$resolvedItem['table']][$resolvedItem['uid']]) {
											$foreignItems[] = &$this->buildArrayByRow($foreignRow, $resolvedItem['table'], $maxDepth - 1);
										}
									}
									
									$params = [
										'tcaConfig' => $config,
									];
									foreach ($this->relationFinisherMethods as $finisherMethod) {
										$params['items'] = $foreignItems;
										$foreignItems = GeneralUtility::callUserFunction($finisherMethod, $params, $this);
									}

									$result[$targetKey] = $foreignItems;
								}
							}
						}

						break;
					case 'flex':
						// TODO: handle that
						$result[$targetKey] = $rawValue;
						break;

					default:
						//DebuggerUtility::var_dump($config, 'not handled yet');
						$result[$targetKey] = $rawValue;
				}

				$params = [
					'row' => $row,
					'table' => $table,
					'uid' => $uid,
					'tcaColumn' => $tcaColumn,
					'tcaConfig' => $config,
					'targetKey' => $targetKey,
				];

				foreach ($this->fieldFinisherMethods as $finisherMethod) {
					$params['value'] = $result[$targetKey];
					$result[$targetKey] = GeneralUtility::callUserFunction($finisherMethod, $params, $this);
				}
			}
		}

		$this->storeItemInStorage($table, $uid, $result);
		return $this->elementStorage[$table][$uid];
	}

	private function collectUnloadedElements()
	{
		$this->loadUnloadedRelationItems();
		$this->loadUnloadedChildItems();
	}

	private function loadUnloadedChildItems()
	{
		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

		foreach ($this->unloadedRelatedChildren as $languageUid => $tables) {
			foreach ($tables as $table => $foreignFields) {
				foreach ($foreignFields as $foreignField => $foreignSortings) {
				 	foreach ($foreignSortings as $foreignSorting => $items) {
						$ids = array_keys($items);
						$idChunks = array_chunk($ids, 1024 / 2);
						$groups = [];

						foreach ($idChunks as $idChunk) {

							$queryBuilder = $connectionPool->getQueryBuilderForTable($table);
							$query = $queryBuilder
								->select('*')
								->from($table)
								->where(
									$queryBuilder->expr()->in($foreignField, $queryBuilder->createNamedParameter($idChunk, Connection::PARAM_INT_ARRAY))
								);

							// order result by sorting field (if set)
							if ($foreignSorting) {
								$query->orderBy($foreignSorting);
							}

							$queryResult = $query->execute();

							while ($row = $queryResult->fetch()) {
								$groups[$row[$foreignField]][] = $this->buildArrayByRow($row, $table, 8, false);
							}
						}

						foreach ($groups as $parentId => $items) {
							$this->loadedRelatedChildren[$languageUid][$table][$foreignField][$foreignSorting][$parentId] = $this->relatedChildren[$languageUid][$table][$foreignField][$foreignSorting][$parentId] = $items;
						}
					}

					unset($this->unloadedRelatedChildren[$languageUid][$table][$foreignField]);
					if (!count($this->unloadedRelatedChildren[$languageUid][$table])) {
						unset($this->unloadedRelatedChildren[$languageUid][$table]);
					}
					if (!count($this->unloadedRelatedChildren[$languageUid])) {
						unset($this->unloadedRelatedChildren[$languageUid]);
					}
				}
			}
		}
	}

	private function loadUnloadedRelationItems()
	{
		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

		foreach ($this->unloadedRelatedItems as $table => $languages) {

			foreach ($languages as $languageUid => $languageItems) {
				$ids = array_keys($languageItems);

				if (!is_array($ids) || !count($ids)) {
					continue;
				}
				$queryBuilder = $connectionPool->getQueryBuilderForTable($table);
				$queryResult = $queryBuilder
					->select('*')
					->from($table)
					->where(
						$this->createLanguageContraints($queryBuilder, $ids, $table, $languageUid)
					)
					->execute();

				$rows = [];
				while ($row = $queryResult->fetch()) {
					$rows[$row['uid']] = $row;
				}

				// add to loaded relations
				if (!key_exists($table, $this->loadedRelatedItems)) {
					$this->loadedRelatedItems[$table] = [];
				}

				foreach ($ids as $id) {
					// check if dataset still exist
					if (array_key_exists($id, $rows)) {
						$reflectedItem = &$this->buildArrayByRow($rows[$id], $table, 8, false);
					} else {
						$reflectedItem = null;
					}

					$this->loadedRelatedItems[$table][$languageUid][$id] = $reflectedItem;
					$this->relatedItems[$table][$languageUid][$id] = $reflectedItem;
				}
			}

			unset($this->unloadedRelatedItems[$table]);
		}
	}



	/**
	 * Helper Method for just loading elements for the matching language
	 * @param QueryBuilder $queryBuilder
	 * @param string $table
	 * @param mixed $singleUidOrUidList
	 * @param ?int $currentLanguageUid
	 * @return null|CompositeExpression
	 */
	private function createLanguageContraints(QueryBuilder $queryBuilder, $singleUidOrUidList, string $table, int $currentLanguageUid = null)
	{
		$uidContstraints = [];
		$languageConstraints = [];

		if (is_array($singleUidOrUidList)) {
			// UID List Mode
			$uidContstraints[] = $queryBuilder->expr()->in(
				'uid',
				$queryBuilder->createNamedParameter($singleUidOrUidList, Connection::PARAM_INT_ARRAY)
			);
		} else {
			// UID Single Mode
			$uidContstraints[] = $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter((int) $singleUidOrUidList, \PDO::PARAM_INT));
		}

		$languageConfig = TcaUtility::getL10nConfig($table);
		if ($languageConfig !== null) {
			$languageField = $languageConfig['languageField'];
			$languageParentField = $languageConfig['transOrigPointerField'];
			$currentLanguageUid = $currentLanguageUid ?? FrontendUtility::getCurrentLanguageId();

			// just load elements from the current language or which are marked for "all languages (-1)"
			$languageConstraints[] = $queryBuilder->expr()->eq($languageField, $currentLanguageUid);
			if ($currentLanguageUid !== -1) {
				$languageConstraints[] = $queryBuilder->expr()->eq($languageField, -1);
			}

			// also load translated elements where the parent is matching
			if (is_array($singleUidOrUidList)) {
				// UID List Mode
				$uidContstraints[] = $queryBuilder->expr()->in(
					$languageParentField,
					$queryBuilder->createNamedParameter($singleUidOrUidList, Connection::PARAM_INT_ARRAY)
				);
			} else {
				// UID Single Mode
				$uidContstraints[] = $queryBuilder->expr()->eq($languageParentField, $queryBuilder->createNamedParameter((int) $singleUidOrUidList, \PDO::PARAM_INT));
			}
		}

		if ($this->debug) {
			DebuggerUtility::var_dump($uidContstraints, 'UID Contraints for ' . $table);
			DebuggerUtility::var_dump($languageConstraints, 'Language Contraints for ' . $table);
		}

		return $queryBuilder->expr()->andX(
			$queryBuilder->expr()->orX(
				...$uidContstraints
			),
			$queryBuilder->expr()->orX(
				...$languageConstraints
			)
		);
	}

	/**
	 * @param string $table
	 * @return void
	 */
	private function createTableStorageIfNotExist(string $table): void
	{
		if (!key_exists($table, $this->elementStorage)) {
			$this->elementStorage[$table] = [];
		}
	}

	/**
	 * @param string $table
	 * @param int $uid
	 * @return bool
	 */
	private function isInStorage(string $table, int $uid): bool
	{
		$this->createTableStorageIfNotExist($table);
		return key_exists($uid, $this->elementStorage[$table]);
	}


	/**
	 * @param string $table
	 * @param int $uid
	 * @param null|array $item
	 * @return void
	 */
	private function storeItemInStorage(string $table, int $uid, ?array $item): void
	{
		$this->createTableStorageIfNotExist($table);
		$this->elementStorage[$table][$uid] = $item;
	}

	/**
	 * Sets multiple properties in one call.
	 * @param array $configuration Configuration settings.
	 * @return ReflectionService
	 */
	public function setPropertiesByConfigurationArray(array $configuration): self
	{
		$tableColumnBlacklist = $this->convertProcessorConfigurationStringListToArray($configuration, 'tableColumnBlacklist');
		if (!empty($tableColumnBlacklist)) {
			$this->addToTableColumnBlacklist($tableColumnBlacklist);
		}

		$tableColumnWhitelist = $this->convertProcessorConfigurationStringListToArray($configuration, 'tableColumnWhitelist');
		if (!empty($tableColumnWhitelist)) {
			$this->setTableColumnWhitelist($tableColumnWhitelist);
		}

		$tableColumnRemoveablePrefixes = $this->convertProcessorConfigurationStringListToArray($configuration, 'tableColumnRemoveablePrefixes');
		if (!empty($tableColumnRemoveablePrefixes)) {
			$this->setTableColumnRemoveablePrefixes($tableColumnRemoveablePrefixes);
		}

		if (!empty($configuration['tableColumnRemapping']) && is_array($configuration['tableColumnRemapping'])) {
			$this->setTableColumnRemapping($configuration['tableColumnRemapping']);
		}

		if (!empty($configuration['buildingConfiguration']) && is_array($configuration['buildingConfiguration'])) {
			$this->setBuildingConfiguration($configuration['buildingConfiguration']);
		}

		if (!empty($configuration['debug'])) {
			$this->setDebug((bool) $configuration['debug']);
		}

		if (isset($configuration['finisher'])) {
			if (isset($configuration['finisher']['field']) && is_array($configuration['finisher']['field'])) {
				$this->setFieldFinisherMethods($configuration['finisher']['field']);
			}

			if (isset($configuration['finisher']['relation']) && is_array($configuration['finisher']['relation'])) {
				$this->setRelationFinisherMethods($configuration['finisher']['relation']);
			}
		}

		return $this;
	}

	/**
	 * @param array $processorConfiguration
	 * @param string $key
	 * @return array
	 */
	private function convertProcessorConfigurationStringListToArray(array $processorConfiguration, string $key): array
	{
		$result = [];
		if (!empty($processorConfiguration[$key]) && is_array($processorConfiguration[$key])) {
			$result = $processorConfiguration[$key];
			foreach ($result as $table => $list) {
				$result[$table] = GeneralUtility::trimExplode(',', $list);
			}
		}
		return $result;
	}

	/**
	 * Get list of columns that are generally not processed
	 *
	 * @return  array
	 */
	public function getColumnBlacklist()
	{
		return $this->columnBlacklist;
	}

	/**
	 * Set list of columns that are generally not processed.
	 *
	 * @param  array  $columnBlacklist  List of columns that are generally not processed.
	 * @return self
	 */
	public function setColumnBlacklist(array $columnBlacklist)
	{
		$this->columnBlacklist = $columnBlacklist;

		return $this;
	}

	/**
	 * Get the value of arrayBuildingConfiguration
	 */
	public function getArrayBuildingConfiguration()
	{
		return $this->buildingConfiguration;
	}

	/**
	 * Get list of table specific columns which aren't processed
	 *
	 * @return  array
	 */
	public function getTableColumnBlacklist()
	{
		return $this->tableColumnBlacklist;
	}

	/**
	 * Set list of table specific columns which aren't processed.
	 *
	 * @param  array  $tableColumnBlacklist  List of table specific columns which aren't processed
	 *
	 * @return  self
	 */
	public function setTableColumnBlacklist(array $tableColumnBlacklist)
	{
		$this->tableColumnBlacklist = $tableColumnBlacklist;

		return $this;
	}

	/**
	 * add to list of table specific columns which aren't processed
	 *
	 * @param  array  $tableColumnBlacklist  List of table specific columns which aren't processed
	 * @return  self
	 */
	public function addToTableColumnBlacklist(array $tableColumnBlacklist)
	{
		ArrayUtility::mergeRecursiveWithOverrule($this->tableColumnBlacklist, $tableColumnBlacklist);
		return $this;
	}

	/**
	 * Get List of tables columns which should be processed exclusively
	 *
	 * @return  array
	 */
	public function getTableColumnWhitelist()
	{
		return $this->tableColumnWhitelist;
	}

	/**
	 * Set List of tables columns which should be processed exclusively.
	 *
	 * @param  array  $tableColumnWhitelist  List of tables columns which should be processed exclusively
	 *
	 * @return  self
	 */
	public function setTableColumnWhitelist(array $tableColumnWhitelist)
	{
		$this->tableColumnWhitelist = $tableColumnWhitelist;

		return $this;
	}

	/**
	 * Get wildcard based Replacement for Column names
	 *
	 * @return  array
	 */
	public function getTableColumnRemoveablePrefixes()
	{
		return $this->tableColumnRemoveablePrefixes;
	}

	/**
	 * Set wildcard based replacement for column names.
	 *
	 * @param  array  $tableColumnRemoveablePrefixes  Wildcard based replacement for column names.
	 *
	 * @return  self
	 */
	public function setTableColumnRemoveablePrefixes(array $tableColumnRemoveablePrefixes)
	{
		$this->tableColumnRemoveablePrefixes = $tableColumnRemoveablePrefixes;

		return $this;
	}

	/**
	 * Get remap Columnnames in reflected result
	 *
	 * @return  array
	 */
	public function getTableColumnRemapping()
	{
		return $this->tableColumnRemapping;
	}

	/**
	 *  Set remap column-names in reflected result.
	 *
	 * @param  array  $tableColumnRemapping  Remapping definition list.
	 *
	 * @return  self
	 */
	public function setTableColumnRemapping(array $tableColumnRemapping)
	{
		$this->tableColumnRemapping = $tableColumnRemapping;

		return $this;
	}

	/**
	 * Get configration for building array like building images (max width, max height ..)
	 *
	 * @return  array
	 */
	public function getBuildingConfiguration()
	{
		return $this->buildingConfiguration;
	}

	/**
	 * Set configration for building array like building images (max width, max height ..)
	 *
	 * @param  array  $buildingConfiguration  Configration for building array like building images (max width, max height ..)
	 *
	 * @return  self
	 */
	public function setBuildingConfiguration(array $buildingConfiguration)
	{
		$this->buildingConfiguration = $buildingConfiguration;

		return $this;
	}

	/**
	 * Get array for used TCA field definitions, helpful for Post-handling that prepared data
	 *
	 * @return  array
	 */
	public function getTcaFieldDefinition()
	{
		return $this->tcaFieldDefinition;
	}

	/**
	 * Get flag for Debug Mode
	 *
	 * @return  bool
	 */
	public function getDebug()
	{
		return $this->debug;
	}

	/**
	 * Set flag for Debug Mode
	 *
	 * @param  bool  $debug  Flag for Debug Mode
	 *
	 * @return  self
	 */
	public function setDebug(bool $debug)
	{
		$this->debug = $debug;

		return $this;
	}

	/**
	 * Get list of php small scale hooks after a element is reflected
	 *
	 * @return  array
	 */
	public function getFieldFinisherMethods()
	{
		return $this->fieldFinisherMethods;
	}

	/**
	 * Set list of php small scale hooks after a element is reflected
	 *
	 * @param  array  $fieldFinisherMethods  List of php small scale hooks after a element is reflected
	 *
	 * @return  self
	 */
	public function setFieldFinisherMethods(array $fieldFinisherMethods)
	{
		$this->fieldFinisherMethods = $fieldFinisherMethods;

		return $this;
	}

	/**
	 * Get enables result cache of buildArrayByRows
	 *
	 * @return bool
	 */ 
	public function getEnableCache()
	{
		return $this->enableCache;
	}

	/**
	 * Set enables result cache of buildArrayByRows
	 *
	 * @param  bool  $enableCache  Enables result cache of buildArrayByRows
	 *
	 * @return  self
	 */ 
	public function setEnableCache(bool $enableCache)
	{
		$this->enableCache = $enableCache;

		return $this;
	}

	/**
	 * Get just load basic fields of related childrens
	 *
	 * @return  bool
	 */ 
	public function getFetchBasicRelationFields()
	{
		return $this->fetchBasicRelationFields;
	}

	/**
	 * Set just load basic fields of related childrens
	 *
	 * @param  bool  $fetchBasicRelationFields  Just load basic fields of related childrens
	 *
	 * @return  self
	 */ 
	public function setFetchBasicRelationFields(bool $fetchBasicRelationFields)
	{
		$this->fetchBasicRelationFields = $fetchBasicRelationFields;

		return $this;
	}

	/**
	 * Get list of php small scale hooks after a relation is reflected
	 *
	 * @return  array
	 */ 
	public function getRelationFinisherMethods()
	{
		return $this->relationFinisherMethods;
	}

	/**
	 * Set list of php small scale hooks after a relation is reflected
	 *
	 * @param  array  $relationFinisherMethods  List of php small scale hooks after a relation is reflected
	 *
	 * @return  self
	 */ 
	public function setRelationFinisherMethods(array $relationFinisherMethods)
	{
		$this->relationFinisherMethods = $relationFinisherMethods;

		return $this;
	}
}
