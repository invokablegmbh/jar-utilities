<?php

declare(strict_types=1);

namespace Jar\Utilities\Utilities;

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */


/** 
 * @package Jar\Utilities\Utilities 
 * Utility Class for working with numbers
 **/

class NumberUtility
{
	/**     
	 * @param mixed $val
	 * @return bool
	 */
	public static function isWholeInt($val): bool
	{
		$val = strval($val);
		$val = str_replace('-', '', $val);

		if (ctype_digit($val)) {
			if ($val === (string)0)
				return true;
			elseif (ltrim($val, '0') === $val)
				return true;
		}

		return false;
	}
}
