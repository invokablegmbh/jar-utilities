<?php

declare(strict_types=1);

namespace Jar\Utilities\Utilities;

use InvalidArgumentException;
use Jar\Utilities\Evaluators\FormData\DisplayConditionEvaluator;
use Jar\Utilities\Services\RegistryService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */


/** 
 * @package Jar\Utilities\Utilities 
 * Utility Class for working faster with the TCA.
 **/

class TcaUtility
{

	/**
	 * Returns active columns by TCA type.
	 * 
	 * @param string $table The table name.
	 * @param string $type The type name.
	 * @return array List of column names.
	 * @throws InvalidArgumentException 
	 */
	public static function getColumnsByType(string $table, string $type): array
	{
		$cache = GeneralUtility::makeInstance(RegistryService::class);
		$hash = $table . '_' . StringUtility::fastSanitize($type);

		if (($columns = $cache->get('tca', $hash)) === false) {
			$tca = self::getTca();
			$columns = self::mapStringListToColumns($tca[$table]['types'][$type]['showitem'] ?? '', $table);
			$cache->set('tca', $hash, $columns);
		}

		return $columns;
	}


	/**
	 * Returns active columns based on a table record.
	 * 
	 * @param string $table The table name.
	 * @param array $row The table record.
	 * @return array List of column names.
	 */
	public static function getColumnsByRow(string $table, array $row): array
	{
		$type = self::getTypeFromRow($table, $row);
		return self::getColumnsByType($table, $type);
	}

	/**
	 * Returns all default columns from a table.
	 * 
	 * @param string $table The table name.
	 * @return array List of column names. 
	 */
	public static function getColumnsByTable(string $table): array
	{
		$tca = self::getTca();
		$firstType = (string) reset(array_keys($tca[$table]['types']));
		return self::getColumnsByType($table, $firstType);
	}


	/**
	 * Returns actice TCA type based on a table record. 
	 * 
	 * @param string $table The table name.
	 * @param array $row The table record.
	 * @return string Name of the type will fallback to default type when no individual type is found.
	 */
	public static function getTypeFromRow(string $table, array $row): string
	{
		$tca = self::getTca();
		$typeField = self::getTypeFieldOfTable($table);

		// use type when defined, otherwise use the first used type
		if ($typeField !== null) {
			$type = $row[$typeField];
		} else {
			$type = reset(array_keys($tca[$table]['types']));
		}

		return (string) $type;
	}


	/**
	 * Just return the columns which are visible for the current Backend User, respects current active display conditions of fields.
	 * 
	 * @param string $table The table name.
	 * @param array $row The table record.
	 * @return array List of column names.
	 */
	public static function getVisibleColumnsByRow(string $table, array $row): array
	{
		$allColums = static::getColumnsByRow($table, $row);
		$type = self::getTypeFromRow($table, $row);
		$displayConditionEvaluator = GeneralUtility::makeInstance(DisplayConditionEvaluator::class);

		$columns = [];
		foreach ($allColums as $column) {
			$definition = static::getFieldDefinition($table, $column, $type);

			// check visibility
			$isDisabled = (bool) BackendUtility::getCurrentPageTS()['TCEFORM'][$table][$column]['disabled'];
			if ($isDisabled) {
				continue;
			}

			// check exclude
			if ($definition['exclude']) {
				if (empty($GLOBALS['BE_USER']) || !$GLOBALS['BE_USER']->isAdmin()) {
					continue;
				}
			}

			// check displayCond
			if (!empty($definition['displayCond']) && !$displayConditionEvaluator->match($definition['displayCond'], $row)) {
				continue;
			}

			$columns[] = $column;
		}
		return $columns;
	}



	/**
	 * Returns the column name which contains the "type" value from a table.
	 * 
	 * @param string $table The table name.
	 * @return string The name of the "type" column.
	 */
	public static function getTypeFieldOfTable(string $table): ?string
	{
		$tca = self::getTca();
		return $tca[$table]['ctrl']['type'];
	}

	/**
	 * Returns the column name which contains the "label" value from a table.
	 * 
	 * @param string $table The table name.
	 * @return string The name of the "label" column.
	 */
	public static function getLabelFieldOfTable(string $table): ?string
	{
		$tca = self::getTca();
		return $tca[$table]['ctrl']['label'];
	}

	/**
	 * Returns the label from a table record.
	 * example ($table 'pages'):
	 *  [ uid => 3, doktype => 254, title => 'Elemente'] results in "Elemente"
	 *
	 * @param array $row The table record.
	 * @param string $table The table name.
	 * @return string|null The label or "null" when empty.
	 */
	public static function getLabelFromRow(array $row, string $table): ?string
	{
		return htmlspecialchars($row[static::getLabelFieldOfTable($table)]);
	}


	/**
	 * Converts a comma-separated list of TCA Columns (a,b,c) to [a,b,c] 
	 * also containing pallet information will be resolved (if table is available)
	 * 
	 * @param string $list Comma-separated list of TCA Columns.
	 * @param string $table The table name.
	 * @param bool $extendedList Flag for returning extendedList.
	 * @return array List of column names or list of [column name | label] when $extendedList is active.
	 */
	public static function mapStringListToColumns(string $list, string $table = null, bool $extendedList = false): array
	{
		$result = [];
		$tca = self::getTca();

		$columns = GeneralUtility::trimExplode(',', $list);
		foreach ($columns as $column) {
			// handle palette sub-columns
			if (strpos($column, '--palette--;') === 0) {
				$palette = $tca[$table]['palettes'][end(GeneralUtility::trimExplode(';', $column))];
				if (!empty($palette)) {
					foreach (self::mapStringListToColumns($palette['showitem'], $table, $extendedList) as $paletteColumn) {
						$result[] = $paletteColumn;
					}
				}
				continue;
			}
			// Delete all other UI Items like --div-- or empty ones
			if (empty($column) || strpos($column, '--') === 0) {
				continue;
			}
			if (!$extendedList) {
				$result[] = reset(explode(';', $column));
			} else {
				// also return the label field
				$result[] = explode(';', $column, 2);
			}
		}
		return $result;
	}


	/**
	 * Returns the current TCA field definition from a table column.
	 * Also resolves column overrides when "type" is set.
	 * 
	 * @param string $table The table name.
	 * @param string $column The column name.
	 * @param null|string $type The type to respect column overrides.
	 * @return null|array The field definition or "null" when no field definition is found.
	 */
	public static function getFieldDefinition(string $table, string $column, ?string $type = null): ?array
	{
		$tca = self::getTca();
		$definition = $tca[$table]['columns'][$column];

		if (empty($definition)) {
			return null;
		}

		if (!empty($type)) {
			// column overrides
			ArrayUtility::mergeRecursiveWithOverrule($definition, $tca[$table]['types'][$type]['columnsOverrides'][$column] ?? []);

			// label overrides via showitem properties
			$indexedShowitemColumns = IteratorUtility::indexBy(self::mapStringListToColumns($tca[$table]['types'][$type]['showitem'] ?? '', $table, true), '0');
			$showItemProperties = $indexedShowitemColumns[$column];
			if (is_array($showItemProperties) && count($showItemProperties) > 1 && !empty($showItemProperties[1])) {
				$definition['label'] = $showItemProperties[1];
			}
		}
		return $definition;
	}


	/**
	 * Returns the TCA field configuration from a table column.
	 * 
	 * @param string $table The table name.
	 * @param string $column The column name.
	 * @param null|string $type The type to respect column overrides.
	 * @return null|array The field configuration or "null" when no field configuration is found.
	 */
	public static function getFieldConfig(string $table, string $column, ?string $type = null): ?array
	{
		return static::getFieldDefinition($table, $column, $type)['config'];
	}

	/**
	 * Converts a TCA item array to a key-based list.
	 * 
	 * example:
	 * [['LLL:.../locallang.xlf:creation', 'uid'], ['LLL:.../locallang.xlf:backendsorting', 'sorting']]
	 * becomes
	 * ['uid' => ['label' => 'LLL:.../locallang.xlf:creation', 'icon' => null], 'sorting' => ['label' => 'LLL:.../locallang.xlf:backendsorting', 'icon' => null]]
	 * 
	 * 
	 * @param array $items TCA item array.
	 * @return array Key-based list.
	 */
	public static function remapItemArrayToKeybasedList(array $items): array
	{
		$result = [];
		foreach ($items as $item) {
			$result[$item[1]] = [
				'value' => $item[0],
				'icon' => $item[2],
			];
		}
		return $result;
	}

	/**
	 * Load the backend label from a selected item.
	 *
	 * @param string $value The selected item. F.e ['LLL:.../locallang.xlf:backendsorting', 'sorting']
	 * @param string $column Column name which contains the selected item.
	 * @param string $table The table name.
	 * @param null|string $type The type to respect column overrides.
	 * @param boolean $localize If false return the raw value, otherwise return the translated value.
	 * @return string|null The label.
	 */
	public static function getLabelOfSelectedItem(string $value, string $column, string $table, ?string $type = null, bool $localize = true): ?string
	{
		$items = static::getFieldConfig($table, $column, $type)['items'] ?? [];
		$items = static::remapItemArrayToKeybasedList($items);
		$result = $items[$value]['value'];
		if ($localize) {
			$result = LocalizationUtility::localize((string) $result);
		}
		return htmlspecialchars($result);
	}


	/**
	 * Returns the TCA language fields from a table or null, if not set.
	 * 
	 * @param string $table The table name.
	 * @return null|array TCA language fields from a table or null, if not set.
	 */
	public static function getL10nConfig(string $table): ?array
	{
		$tableDefinition = static::getTca()[$table];
		$l10nEnabled = $tableDefinition['ctrl']['languageField'] && $tableDefinition['ctrl']['transOrigPointerField'];
		if (!$l10nEnabled) {
			return null;
		}

		return [
			'languageField' => $tableDefinition['ctrl']['languageField'],
			'transOrigPointerField' => $tableDefinition['ctrl']['transOrigPointerField'],
		];
	}


	/**
	 * Returns the current TCA.
	 * 
	 * @return array The TCA.
	 */
	public static function getTca(): array
	{
		return $GLOBALS['TCA'];
	}
}
