<?php

declare(strict_types=1);

namespace Jar\Utilities\Utilities;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */


/** 
 * @package Jar\Utilities\Utilities 
 * Load and render content elements.
 **/

class ContentUtility
{

	/**
	 * Render the frontend output of a content element.
	 * @param string $uid The content element uid.
	 * @throws Exception
	 * @return string The rendered markup.
	 */
	public static function renderElement($uid = null)
	{

		if (empty($uid)) {
			return '';
		}

		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		$ce = $cObj->cObjGetSingle('RECORDS', [
			'tables' => 'tt_content',
			'source' => $uid,
			'dontCheckPid' => 1,
		]);

		return $ce;
	}
}
