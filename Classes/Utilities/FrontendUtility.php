<?php

declare(strict_types=1);

namespace Jar\Utilities\Utilities;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Context\Context;
/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */


/** 
 * @package Jar\Utilities\Utilities 
 * Utility Class for FrontendUtility Tasks
 **/

class FrontendUtility
{

	/** @return int  */
	public static function getCurrentPageUid(): int
	{
		return (int) $GLOBALS['TSFE']->id;
	}


	/**
	 * @return int 
	 */
	public static function getCurrentLanguageId(): int
	{
		$context = GeneralUtility::makeInstance(Context::class);
		return (int) $context->getPropertyFromAspect('language', 'id');
	}
}
