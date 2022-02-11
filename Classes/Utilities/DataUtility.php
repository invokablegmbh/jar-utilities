<?php

declare(strict_types=1);

namespace Jar\Utilities\Utilities;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */


/** 
 * @package Jar\Utilities\Utilities 
 **/

class DataUtility
{
	/**
	 * Shorthand if you just want to load one item from a table
	 * @param string $table 
	 * @param int $uid 
	 * @return array 
	 * @throws InvalidArgumentException 
	 */
	public static function getRow(string $table, int $uid): array
	{
		$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
		return $queryBuilder
			->select('*')
			->from($table)
			->where(
				$queryBuilder->expr()->eq(
					'uid',
					$queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
				),
			)
			->execute()
			->fetch();
	}
}
