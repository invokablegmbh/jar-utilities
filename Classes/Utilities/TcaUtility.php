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
 * Utility Class for working faster with the TCA
 **/

class TcaUtility
{

	/**
	 * @param string $table 
	 * @param string $type 
	 * @return array 
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
	 * @param string $table 
	 * @param array $row 
	 * @return array 
	 */
	public static function getColumnsByRow(string $table, array $row): array
	{
		$type = self::getTypeFromRow($table, $row);
		return self::getColumnsByType($table, $type);
	}

	/**
	 * @param string $table 	 
	 * @return array 	 
	 */
	public static function getColumnsByTable(string $table): array
	{
		$tca = self::getTca();
		$firstType = (string) reset(array_keys($tca[$table]['types']));
		return self::getColumnsByType($table, $firstType);
	}


	/**
	 * @param string $table 
	 * @param array $row 
	 * @return string 
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
	 * just return the columns which are visible for the current Backend User
	 * 
	 * @param string $table 
	 * @param array $row 
	 * @return array 
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
	 * @param string $table 
	 * @return string 
	 */
	public static function getTypeFieldOfTable(string $table): ?string
	{
		$tca = self::getTca();
		return $tca[$table]['ctrl']['type'];
	}

	/**
	 * @param string $table 
	 * @return string 
	 */
	public static function getLabelFieldOfTable(string $table): ?string
	{
		$tca = self::getTca();
		return $tca[$table]['ctrl']['label'];
	}

	/**
	 * Return the label from a given row
	 * example ($table 'pages'):
	 *  [ uid => 3, doktype => 254, title => 'Elemente'] results in "Elemente"
	 *
	 * @param array $row
	 * @param string $table
	 * @return string|null
	 */
	public static function getLabelFromRow(array $row, string $table): ?string
	{
		return htmlspecialchars($row[static::getLabelFieldOfTable($table)]);
	}


	/**
	 * Converts a list of TCA Columns (a,b,c) to [a,b,c] 
	 * also pallet information is resolved (if $table is available)
	 * 
	 * @param string $list 
	 * @param string $table 
	 * @return array	  
	 */
	public static function mapStringListToColumns(string $list, string $table = null): array
	{
		$result = [];
		$tca = self::getTca();

		$columns = GeneralUtility::trimExplode(',', $list);
		foreach ($columns as $column) {
			// handle palette sub-columns
			if (strpos($column, '--palette--;') === 0) {
				$palette = $tca[$table]['palettes'][end(GeneralUtility::trimExplode(';', $column))];
				if (!empty($palette)) {
					foreach (self::mapStringListToColumns($palette['showitem'], $table) as $paletteColumn) {
						$result[] = $paletteColumn;
					}
				}
				continue;
			}
			// Delete all other UI Items like --div-- or empty ones
			if (empty($column) || strpos($column, '--') === 0) {
				continue;
			}
			$result[] = reset(explode(';', $column));
		}
		return $result;
	}


	/**
	 * @param string $table
	 * @param string $column 
	 * @param null|string $type type to respect column overrides
	 * @return null|array 
	 */
	public static function getFieldDefinition(string $table, string $column, ?string $type = null): ?array
	{
		$tca = self::getTca();
		$definition = $tca[$table]['columns'][$column];

		if (empty($definition)) {
			return null;
		}

		if (!empty($type)) {
			ArrayUtility::mergeRecursiveWithOverrule($definition, $tca[$table]['types'][$type]['columnsOverrides'][$column] ?? []);
		}
		return $definition;
	}


	/**
	 * @param string $table
	 * @param string $column 
	 * @param null|string $type type to respect column overrides
	 * @return null|array 
	 */
	public static function getFieldConfig(string $table, string $column, ?string $type = null): ?array
	{
		return static::getFieldDefinition($table, $column, $type)['config'];
	}

	/**
	 * converts a TCA item array to a keybased List
	 * example:
	 * [['LLL:.../locallang.xlf:creation', 'uid'], ['LLL:.../locallang.xlf:backendsorting', 'sorting']]
	 * becomes
	 * ['uid' => ['label' => 'LLL:.../locallang.xlf:creation', 'icon' => null], 'sorting' => 'label' => 'LLL:.../locallang.xlf:backendsorting', 'icon' => null]]
	 * 
	 * 
	 * @param array $items 
	 * @return array 
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
	 * Load the Backendlabel of an selected item
	 *
	 * @param string $value
	 * @param string $column
	 * @param string $table
	 * @param null|string $type
	 * @param boolean $localize if false return the raw valiue, otherwise return the translated value
	 * @return string|null
	 */
	public static function getLabelOfSelectedItem(string $value, string $column, string $table, ?string $type = null, bool $localize = true): ?string
	{
		$items = static::getFieldConfig($table, $column, $type)['items'] ?? [];
		$items = static::remapItemArrayToKeybasedList($items);
		$result = $items[$value]['value'];
		if($localize) {
			$result = LocalizationUtility::localize((string) $result);
		}
		return htmlspecialchars($result);
	}


	/**
	 * returns the TCA language fields of a table or null, if not set
	 * @param mixed $table 
	 * @return null|array 
	 */
	public static function getL10nConfig($table): ?array {
		$tableDefinition = static::getTca()[$table];
		$l10nEnabled = $tableDefinition['ctrl']['languageField'] && $tableDefinition['ctrl']['transOrigPointerField'];
		if(!$l10nEnabled) {
			return null;
		}

		return [
			'languageField' => $tableDefinition['ctrl']['languageField'],
			'transOrigPointerField' => $tableDefinition['ctrl']['transOrigPointerField'],
		];
	}


	/** @return array  */
	public static function getTca(): array
	{
		return $GLOBALS['TCA'];
	}
}
