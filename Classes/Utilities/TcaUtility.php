<?php

declare(strict_types=1);

namespace Jar\Utilities\Utilities;

use InvalidArgumentException;
use Jar\Utilities\Services\RegistryService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;

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
			$columns = self::mapStringListToColumns($tca[$table]['types'][$type]['showitem'], $table);
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
		$tca = self::getTca();
		$typeField = self::getTypeFieldOfTable($table);

		// use type when defined, otherwise use the first used type
		if ($typeField !== null) {
			$type = $row[$typeField];
		} else {
			$type = reset(array_keys($tca[$table]['types']));
		}

		return self::getColumnsByType($table, (string) $type);
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
	public static function getFieldConfig(string $table, string $column, ?string $type = null): ?array
	{
		$tca = self::getTca();
		$columnConfig = $tca[$table]['columns'][$column];

		if (empty($columnConfig)) {
			return null;
		}

		if (!empty($type)) {
			ArrayUtility::mergeRecursiveWithOverrule($columnConfig, $tca[$table]['types'][$type]['columnsOverrides'][$column] ?? []);
		}
		return $columnConfig['config'];
	}


	/** @return array  */
	public static function getTca(): array
	{
		return $GLOBALS['TCA'];
	}
}
