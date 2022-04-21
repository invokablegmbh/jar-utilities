<?php

declare(strict_types=1);

namespace Jar\Utilities\Utilities;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */


/** 
 * @package Jar\Utilities\Utilities 
 * Utility Class for working with numbers.
 **/

class NumberUtility
{
	/**
	 * Checks if the value represents a whole number (integer).
	 * 
	 * @param mixed $val The value to check.
	 * @return bool "True" if is a whole number else return "false".
	 */
	public static function isWholeInt($val): bool
	{
		$val = strval($val);
		$val = str_replace('-', '', $val);

		if (ctype_digit($val)) {
			if ($val === (string)0)
				return true;
			elseif (ltrim($val, '0') == $val)
				return true;
		}

		return false;
	}
}
