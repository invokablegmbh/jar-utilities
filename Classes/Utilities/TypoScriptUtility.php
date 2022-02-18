<?php

declare(strict_types=1);

namespace Jar\Utilities\Utilities;

use InvalidArgumentException;
use Jar\Utilities\Services\RegistryService;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

/** @package Jar\Utilities\Utilities */
class TypoScriptUtility
{
	/**
	 * Loads current TypoScript like TypoScriptUtility::get('plugin.tx_jarfeditor.settings')
	 * 
	 * @param string|null $path Dot notated TypoScript Path
	 * @param int|null $pageUid PageUid from which page the TypoScript should be loaded (optional in Frontend)
	 * @param bool $populated should the Data be populated (f.e. "element = TEXT / element.value = Bla" => "element = Bla")
	 * @return array 
	 * @throws InvalidArgumentException
	 */
	public static function get(string $path = null, int $pageUid = null, bool $populated = false): array
	{
		$cache = GeneralUtility::makeInstance(RegistryService::class);

		$cachePage = $pageUid === null ? BackendUtility::currentPageUid() : $pageUid;
		$hash = $path . '_' . ((int)$cachePage) . '_' . $populated;

		if (($ts_array = $cache->get('ts', $hash)) === false) {
			if ($pageUid === null && TYPO3_MODE === 'FE') {
				$setup = $GLOBALS['TSFE']->tmpl->setup;
			} else if ($pageUid) {
                $setup = static::loadTypoScript($pageUid);
            }

			$setup = empty($setup) ? [] : $setup;

			$ts_array = static::convertTypoScriptArrayToPlainArray($setup);

			if (!empty($path)) {
				$ts_array = static::getRecursiveKeyFromArray($ts_array, explode('.', $path));
			}

			if ($populated) {
				$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
				$ts_array = $cObj->cObjGetSingle($ts_array['_typoScriptNodeValue'], $ts_array['data']);
			}


			$cache->set('ts', $hash, $ts_array);
		}

		return $ts_array ?? [];
	}



	/**
	 * Helper for traversing arrays with dot-based keys f.e. "x.y.z" returns $array['x']['y']['z']
	 * 
	 * @param array $array 
	 * @param array $keylist 
	 * @return null|array 
	 */
	protected static function getRecursiveKeyFromArray(array $array, array $keylist): ?array
	{
		if (!count($keylist)) {
			return $array;
		}
		$firstKey = reset($keylist);
		if (!isset($array[$firstKey])) {
			return null;
		} else {
			return static::getRecursiveKeyFromArray($array[$firstKey], array_slice($keylist, 1));
		}
	}



	/**
	 * Helper for loading the whole TypoScript of a certain page
	 * 
	 * @param int|null $pageUid 
	 * @return array 
	 * @throws InvalidArgumentException 
	 */
	protected static function loadTypoScript(int $pageUid = null): array
	{
		// Bloody fallback to first used Page, if page is emtpy
		if ($pageUid === null) {
			$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages')->createQueryBuilder();
			$queryBuilder->select('uid')->from('pages')->setMaxResults(1);
			$pageUid = reset($queryBuilder->execute());
		}
		$pageUid = intval($pageUid);
		$rootLineUtility = GeneralUtility::makeInstance(RootlineUtility::class, [$pageUid]);
		$TSObj = GeneralUtility::makeInstance(ExtendedTemplateService::class);
		$TSObj->runThroughTemplates($rootLineUtility->get());
		$TSObj->generateConfig();

		return empty($TSObj->setup) ? [] : $TSObj->setup;
	}



	/**
	 * Wrapper for the Core convertTypoScriptArrayToPlainArray
	 * 
	 * @param array $typoscriptArray 
	 * @return null|array 
	 * @throws InvalidArgumentException 
	 */
	public static function convertTypoScriptArrayToPlainArray(?array $typoscriptArray): array
	{
		if (!is_array($typoscriptArray)) {
			return [];
		}
		$typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
		return $typoScriptService->convertTypoScriptArrayToPlainArray($typoscriptArray) ?? [];
	}


	/**
	 * Resolves cObjects and leaves values without deeper configuration as they are
	 * 
	 * from:
	 *  hello = world
	 * 	element = TEXT 
	 * 	element.value = Bla
	 *	tree.value = Blupp
	 * to:
	 *  hello = world
	 * 	element = Bla
	 *  tree.value = Blupp
	 * 
	 * @param array $conf 
	 * @param null|ContentObjectRenderer $cObj
	 * @return array 
	 * @throws InvalidArgumentException 
	 */
	public static function populateTypoScriptConfiguration(array $conf, ?ContentObjectRenderer $cObj = null): array
	{
		if($cObj === null) {
			$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		}
		foreach ($conf as $key => $c) {

			$isChild = (substr((string) $key, -1) === '.');	// f.e. "bla."

			if (!$isChild) {
				// Just populate fields with subinformations
				if (!key_exists($key . '.', $conf)) {
					continue;
				}

				// just populate registered cObjects
				$cObjects = array_keys($GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']);
				if(!in_array($c, $cObjects)) {
					continue;
				}

				$conf[$key] = $cObj->cObjGetSingle($c, $conf[$key . '.']);
			} else {
				$parentKey = substr($key, 0, -1);
				$hasParent = key_exists($parentKey, $conf);

				// Delete Subinformations, because they are populated earlier
				unset($conf[$key]);

				// when no Parent exist save the values and remove the . at the end
				if (!$hasParent) {
					// also populate substructure
					$c = static::populateTypoScriptConfiguration($c, $cObj);
					$conf[$parentKey] = $c;
				}
			}
		}
		return $conf;
	}
}
