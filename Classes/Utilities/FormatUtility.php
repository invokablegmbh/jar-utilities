<?php

declare(strict_types=1);

namespace Jar\Utilities\Utilities;

use InvalidArgumentException;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */


/** 
 * @package Jar\Utilities\Utilities 
 * Utility Class which mainly converts TYPO3 Backend strings to handy arrays
 **/

class FormatUtility
{
	/**
	 * Converts t3link parameters to a list of ready-to-use link informations.
	 * @param string $params T3link parameters.
	 * @return null|array Link informations or null when failed.
	 */
	public static function buildLinkArray(?string $params): ?array
	{

		if (empty($params)) {
			return null;
		}

		$target = '';
		$text = '';
		$class = '';

		$parts = str_getcsv($params, ' ');

		if (!empty($parts[1]) && $parts[1] != '-')
			$target = $parts[1];
		if (!empty($parts[2]) && $parts[2] != '-')
			$class = $parts[2];
		if (!empty($parts[3]) && $parts[3] != '-')
			$text = stripslashes($parts[3]);

		$cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);

		$url = $cObj->typolink_URL([
			'parameter' => $parts[0],
			'forceAbsoluteUrl' => 1,
		]);

		// part[0] with "mailto:" generates "#", so we will also check for this
		if (empty($url) || $url === '#') {
			$url = $parts[0];
		}

		$origUrl = $url;
		$addParams = '';
		if (!empty($parts[4]) && $parts[4] != '-') {
			$origUrl .= $parts[4];
			$addParams = $parts[4];
		}

		return array(
			'url' => $origUrl,
			'base' => $url,
			'params' => $addParams,
			'target' => $target,
			'text' => $text,
			'class' => $class,
			'raw' => $params,
		);
	}



	/**
	 * Build time information for a stored time. 
	 * @param int $time Time in seconds.
	 * @return null|array Time informations or null when failed.
	 */
	public static function buildTimeArray(int $time): ?array
	{
		if (empty($time)) {
			return null;
		}
		$timeFormated = gmdate('H:i', (int)$time);
		return [
			'timeForSorting' => $time,
			'formatedTime' => $timeFormated
		];
	}

	/**
	 * Build date informations from a date string.
	 * @param string $date Date string.
	 * @return null|array Date informations or null when failed.
	 */

	public static function buildDateTimeArrayFromString(string $date): ?array
	{
		if (empty($date)) {
			return null;
		}

		if($date === '0000-00-00 00:00:00' || $date === '0000-00-00') {
			return null;
		}

		if(strtotime($date . ' UTC') == false) {
			return self::buildDateTimeArray( new \DateTime( date('c', (int)$date) ) );
		}
		
		return self::buildDateTimeArray(new \DateTime(date('c', strtotime($date . ' UTC'))));
	}

	/**
	 * Build date informations from a DateTime object.
	 * @param \DateTime $date DateTime object.
	 * @return null|array Date informations or null when failed.
	 */
	public static function buildDateTimeArray(\DateTime $date): ?array
	{
		if (empty($date)) {
			return null;
		}

		$timeZoneObj = new \DateTimeZone('UTC');
		$date->setTimezone($timeZoneObj);
		$unix = $date->getTimestamp();

		$return = [
			'unix' => $unix,
			'day' => $date->format('d'),
			'dayNonZero' => $date->format('j'),
			'weekDayText' => strftime("%A", $unix),
			'weekDayTextShort' => strftime("%a", $unix),
			'month' => $date->format('m'),
			'monthText' => strftime("%B", $unix),
			'monthTextShort' => strftime("%b", $unix),
			'year' => $date->format('Y'),
			'hour' => $date->format('H'),
			'minute' => $date->format('i'),
			'second' => $date->format('s'),
			'dateForSorting' => $date->format('Y-m-d'),
			'formatedDate' => $date->format('d.m.Y'),
			'formatedDateShort' => $date->format('d.m.y'),
			'formatedDateShorter' => $date->format('d.m.'),
			'dayOfWeek' => $date->format('N'),
			'weekOfYear' => $date->format("W"),
			'formatedTime' => $date->format('H:i'),
		];

		return $return;
	}


	/**
	 * Compiles rich-text to the final markup.
	 * @param string $value The rich-text.
	 * @return string The final markup.
	 * @throws InvalidArgumentException 
	 */
	public static  function renderRteContent(string $value): string
	{
		$contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		$conf = [];
		if(ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend() === false) {
			$conf = static::getParseFuncConf();
		};
		$content = str_replace('&amp;shy;', '&shy;', $contentObject->parseFunc($value, $conf, '< lib.parseFunc_RTE'));
		return $content;
	}

	protected static function getParseFuncConf() {
		return array ( 'makelinks' => 0, 'makelinks.' => array ( 'http.' => array ( 'keep' => 'path', 'extTarget' => '_blank', ), 'mailto.' => array ( 'keep' => 'path', ), ), 'tags.' => array ( 'a' => 'TEXT', 'a.' => array ( 'current' => '1', 'typolink.' => array ( 'parameter.' => array ( 'data' => 'parameters:href', ), 'title.' => array ( 'data' => 'parameters:title', ), 'ATagParams.' => array ( 'data' => 'parameters:allParams', ), 'target.' => array ( 'ifEmpty.' => array ( 'data' => 'parameters:target', ), ), 'extTarget.' => array ( 'ifEmpty.' => array ( 'override' => '_blank', ), 'override.' => array ( 'data' => 'parameters:target', ), ), ), ), ), 'allowTags' => 'a, abbr, acronym, address, article, aside, b, bdo, big, blockquote, br, caption, center, cite, code, col, colgroup, dd, del, dfn, dl, div, dt, em, figure, font, footer, header, h1, h2, h3, h4, h5, h6, hr, i, img, ins, kbd, label, li, link, meta, nav, ol, p, pre, q, s, samp, sdfield, section, small, span, strike, strong, style, sub, sup, table, thead, tbody, tfoot, td, th, tr, title, tt, u, ul, var', 'denyTags' => '*', 'constants' => '1', 'nonTypoTagStdWrap.' => array ( 'HTMLparser' => '1', 'HTMLparser.' => array ( 'keepNonMatchedTags' => '1', 'htmlSpecialChars' => '2', ), ), 'htmlSanitize' => '1', );
	}
}
