<?php

declare(strict_types=1);

namespace Jar\Utilities\Utilities;

define('FNM_PATHNAME', 1);
define('FNM_NOESCAPE', 2);
define('FNM_PERIOD', 4);
define('FNM_CASEFOLD', 16);

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */


/** 
 * @package Jar\Utilities\Utilities 
 * Utility Class for handling wildcard opertations like "b?a_*"
 * Based on me@rowanlewis.com from https://www.php.net/manual/de/function.fnmatch.php
 **/

class WildcardUtility
{

	/**
	 * Matches a string against a whole list of patterns, returns "true" on first match
	 * 
	 * @param array $patterns 
	 * @param string $string 
	 * @param int $flags 
	 * @return bool 
	 */
	public static function matchAgainstPatternList(array $patterns, string $string, int $flags = 0): bool
	{
		foreach ($patterns as $pattern) {
			$result = static::match($pattern, $string, $flags);
			if ($result) {
				return true;
			}
		}
		return false;
	}
	

	/**
	 * Simple wildcard matching, matches "hello*world"
	 * "hello beatiful world": true
	 * "hello happy planet": false
	 * 
	 * Wildcards like * or ? are useable
	 * 
	 * @param string $pattern 
	 * @param string $string 
	 * @param int $flags 
	 * @return bool 
	 */
	public static function match(string $pattern, string $string, int $flags = 0): bool
	{

		if (function_exists('fnmatch')) {
			return fnmatch($pattern, $string, $flags);
		}

		$pattern = static::createRegexPattern($pattern, $flags);

		// Period at start must be the same as pattern:
		if ($flags & FNM_PERIOD) {
			if (strpos($string, '.') === 0 && strpos($pattern, '.') !== 0) return false;
		}

		return (bool)preg_match($pattern, $string);
	}

	/**
	 * Converts a wildcard-bases pattern to string to a Regex-useable pattern
	 * f.e. 'bla_*' becomes '#^bla_.*$#'
	 * @param string $wildcardPattern 
	 * @param int $flags 
	 * @return string 
	 */
	private static function createRegexPattern(string $wildcardPattern, int $flags = 0): string
	{
		$modifiers = null;
		$transforms = array(
			'\*'    => '.*',
			'\?'    => '.',
			'\[\!'    => '[^',
			'\['    => '[',
			'\]'    => ']',
			'\.'    => '\.',
			'\\'    => '\\\\'
		);

		// Forward slash in string must be in pattern:
		if ($flags & FNM_PATHNAME) {
			$transforms['\*'] = '[^/]*';
		}

		// Back slash should not be escaped:
		if ($flags & FNM_NOESCAPE) {
			unset($transforms['\\']);
		}

		// Perform case insensitive match:
		if ($flags & FNM_CASEFOLD) {
			$modifiers .= 'i';
		}

		$regexPattern = '#^'
			. strtr(preg_quote($wildcardPattern, '#'), $transforms)
			. '$#'
			. $modifiers;

		return $regexPattern;
	}
}
