<?php

declare(strict_types=1);

namespace Jar\Utilities\Services;

use Doctrine\DBAL\DBALException;
use InvalidArgumentException;
use RuntimeException;
use ReflectionException;
use Jar\Utilities\Utilities\FileUtility;
use Jar\Utilities\Utilities\FormatUtility;
use Jar\Utilities\Utilities\FrontendUtility;
use Jar\Utilities\Utilities\IteratorUtility;
use Jar\Utilities\Utilities\TcaUtility;
use Jar\Utilities\Utilities\WildcardUtility;
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

	/**
	 * Reflects a list of record rows.
	 * 
	 * @param array $rows The record list.
	 * @param string $table The tablename.
	 * @param int $maxDepth The maximum depth at which related elements are loaded (default is 8).
	 * @return array Reflected result.
	 * @throws InvalidArgumentException 
	 * @throws RuntimeException 
	 * @throws TooDirtyException 
	 * @throws ReflectionException 
	 */
	public function buildArrayByRows(array $rows, string $table, int $maxDepth = 8): array
	{
		$result = [];
		foreach ($rows as $key => $row) {
			$result[$key] = $this->buildArrayByRow($row, $table);
		}
		return $result;
	}


	/**
	 * Reflects a single record row.
	 * 
	 * @param array $row The record row.
	 * @param string $table The tablename.
	 * @param int $maxDepth The maximum depth at which related elements are loaded (default is 8).
	 * @return null|array 
	 * @throws InvalidArgumentException 
	 * @throws RuntimeException 
	 * @throws TooDirtyException 
	 * @throws ReflectionException 
	 */
	public function &buildArrayByRow(array $row, string $table, int $maxDepth = 8): ?array
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
			$config = $tcaDefinition['config'];

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
				$result[$targetKey] = $row[$tcaColumn];
			} else {
				// populate the raw row informations (unless it is a password field)
				if (!empty($config['eval'])) {
					if (strpos($config['eval'], 'password') !== false) {
						continue;
					}
				}

				$rawValue = $row[$tcaColumn];

				switch ($config['type']) {
					case 'input':
						switch ($config['renderType']) {
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
						if (!$config['enableRichtext']) {
							$result[$targetKey] = nl2br($rawValue ?? '');
						} else {
							$result[$targetKey] = FormatUtility::renderRteContent($rawValue);
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
							($config['type'] === 'group' && $config['internal_type'] !== 'db') ||
							($config['type'] !== 'group' && empty($config['foreign_table']))
						) {
							$result[$targetKey] = ((int) $config['maxitems'] > 1) ? GeneralUtility::trimExplode(',', $rawValue, true) : $rawValue;
							break;
						}
						// Load relations to other tables												
						$relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
						$relationHandler->start($rawValue, ($config['type'] === 'group') ? $config['allowed'] : $config['foreign_table'], $config['MM'], $uid, $table, $config);
						$relationHandler->getFromDB();
						$resolvedItemArray = $relationHandler->getResolvedItemArray();

						if (empty($resolvedItemArray)) {
							$result[$targetKey] = [];
							break;
						}

						// handle sys_file_references directly, no recursive resolving
						if ($config['foreign_table'] === 'sys_file_reference') {
							$result[$targetKey] = [];
							foreach ($resolvedItemArray as $resolvedItem) {
								if (!empty($resolvedItem['uid'])) {
									$fileBuidlingConfiguration = $this->buildingConfiguration['file'] ?? [];
									$cropVariants = $config['overrideChildTca']['columns']['crop']['config']['cropVariants'];
									ArrayUtility::mergeRecursiveWithOverrule($fileBuidlingConfiguration, [
										'tcaCropVariants' => $cropVariants
									]);

									$fileArray = FileUtility::buildFileArrayBySysFileReferenceUid($resolvedItem['uid'], $fileBuidlingConfiguration);

									if (!empty($fileArray)) {
										$result[$targetKey][] = $fileArray;
									}
								}
							}
						} else {

							// When we have just sub-relations from one other table, make huge select, otherwise load row for row
							$foreignTables = IteratorUtility::pluck($resolvedItemArray, 'table');
							$foreignTableAmount = Count(array_unique($foreignTables));
							$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

							// set currentLanguage to the language of the row
							$currentLanguageUid = $row['sys_language_uid'];
							if ($foreignTableAmount === 1) {
								// TODO: Just load Elements which aren't loaded yet
								$foreignTable = reset($foreignTables);
								$queryBuilder = $connectionPool->getQueryBuilderForTable($foreignTable);
								$selectUids = IteratorUtility::pluck($resolvedItemArray, 'uid');
								$queryResult = $queryBuilder
									->select('*')
									->from($foreignTable)
									->where(
										$this->createLanguageContraints($queryBuilder, $selectUids, $foreignTable, $currentLanguageUid)
									)
									->execute();

								$foreignItems = [];
								while ($foreignRow = $queryResult->fetch()) {
									$sortUid = array_search($foreignRow['uid'], $selectUids);
									$foreignItems[$sortUid] = &$this->buildArrayByRow($foreignRow, $foreignTable, $maxDepth - 1);
								}
								ksort($foreignItems);
								$result[$targetKey] = $foreignItems;
							} else {
								$foreignItems = [];
								foreach ($resolvedItemArray as $resolvedItem) {
									$queryBuilder = $connectionPool->getQueryBuilderForTable($resolvedItem['table']);
									$queryResult = $queryBuilder
										->select('*')
										->from($resolvedItem['table'])
										->where(
											$this->createLanguageContraints($queryBuilder, $resolvedItem['uid'], $resolvedItem['table'], $currentLanguageUid)
										)
										->execute();
									if ($foreignRow = $queryResult->fetch()) {
										$foreignItems[] = &$this->buildArrayByRow($foreignRow, $resolvedItem['table'], $maxDepth - 1);
									}
								}
								$result[$targetKey] = $foreignItems;
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
			}
		}

		$this->storeItemInStorage($table, $uid, $result);
		return $this->elementStorage[$table][$uid];
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
}
