<?php

declare(strict_types=1);

namespace Jar\Utilities\Utilities;

use InvalidArgumentException;
use Jar\Utilities\Services\RegistryService;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScriptFactory;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Core\Site\SiteFinder;

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

/** 
 * @package Jar\Utilities\Utilities
 * Load and progress faster with TypoScript.
 */


class TypoScriptUtility
{
	/**
	 * Loads current TypoScript like TypoScriptUtility::get('plugin.tx_jarfeditor.settings')
	 * 
	 * @param string|null $path Dot notated TypoScript Path
	 * @param int|null $pageUid PageUid from which page the TypoScript should be loaded (optional in Frontend)
	 * @param bool $populated should the Data be populated (f.e. "element = TEXT / element.value = Bla" => "element = Bla")
	 * @return array|string
	 * @throws InvalidArgumentException
	 */
	public static function get(string $path = null, int $pageUid = null, bool $populated = false)
	{
		$cache = GeneralUtility::makeInstance(RegistryService::class);

		$cachePage = $pageUid ?? BackendUtility::currentPageUid();
		$hash = $path . '_' . ((int)$cachePage) . '_' . $populated;
		if (($ts_array = $cache->get('ts', $hash)) === false) {
			if (isset($GLOBALS['TSFE']) && $pageUid === null && (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
			&& ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
		)) {
				$setup = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.typoscript')->getSetupArray();
			} else {
                $setup = static::loadTypoScript($pageUid);
            }
			$setup = empty($setup) ? [] : $setup;
			
			$ts_array = static::convertTypoScriptArrayToPlainArray($setup);

			if (!in_array($path, [null, '', '0'], true)) {
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
	 * @return null|string|array 
	 */
	protected static function getRecursiveKeyFromArray(array $array, array $keylist)
	{
		if (!count($keylist)) {
			return $array;
		}
		$firstKey = reset($keylist);
		if (!isset($array[$firstKey])) {
            return null;
        } elseif (!is_array($array[$firstKey])) {
            return $array[$firstKey];
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
			$pageUid = $queryBuilder->executeQuery()->fetchOne();
		}

		if(empty($pageUid)) {
			return [];
		}

		$pageUid = intval($pageUid);
		
		// TYPO3 v13 compatibility: In backend context, use container to get FrontendTypoScriptFactory
		try {
			$siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
			$site = $siteFinder->getSiteByPageId($pageUid);
			$rootLineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $pageUid);
			$rootLine = $rootLineUtility->get();
			
			// Get sys_template rows for the page
			$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
				->getQueryBuilderForTable('sys_template');
			$sysTemplateRows = $queryBuilder
				->select('*')
				->from('sys_template')
				->where(
					$queryBuilder->expr()->in('pid', array_column($rootLine, 'uid'))
				)
				->orderBy('pid')
				->addOrderBy('sorting')
				->executeQuery()
				->fetchAllAssociative();
			
			// Get FrontendTypoScriptFactory from container with all dependencies
			$container = GeneralUtility::getContainer();
			$frontendTypoScriptFactory = $container->get(FrontendTypoScriptFactory::class);
			$frontendTypoScript = $frontendTypoScriptFactory->createSettingsAndSetupConditions(
				$site,
				$sysTemplateRows,
				[], // expressionMatcherVariables
				null // typoScriptCache
			);
			
			return $frontendTypoScript->getSetupArray();
		} catch (\Exception) {
			// Fallback to empty array if something goes wrong
			return [];
		}
	}



	/**
	 * Wrapper for the Core convertTypoScriptArrayToPlainArray
	 * 
	 * @param array $typoscriptArray A TypoScript array.
	 * @return null|array The plain TypoScript array or "null" when not found.
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
	 * @param array $conf Plain TypoScript array.
	 * @param null|ContentObjectRenderer $cObj ContentObject which should be used.
	 * @return array|string The plain populated TypoScript array.
	 * @throws InvalidArgumentException 
	 */
	public static function populateTypoScriptConfiguration(array $conf, ?ContentObjectRenderer $cObj = null, int $maxNesting = 100)
	{
		if($maxNesting <= 0) {
			return [];
		}

		if(!$cObj instanceof ContentObjectRenderer) {
			$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		}

		if (GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() < 12) {			
			$availableCObjects = array_keys($GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']);
		} else {
			// not used in TYPO3 12 anymore
			$availableCObjects = [];
		}
		

		// f.e. flat typoScript Objects with "_typoScriptNodeValue"
		$isFlatCObject = (is_array($conf) && array_key_exists('_typoScriptNodeValue', $conf) && in_array($conf['_typoScriptNodeValue'], $availableCObjects));

		if ($isFlatCObject) {
			return $cObj->cObjGetSingle($conf['_typoScriptNodeValue'], $conf);
		}
		
		foreach ($conf as $key => $c) {

			/* f.e.:				
				bla. = .... # <- This
			*/

			$isSubConfiguration = (str_ends_with((string) $key, '.'));

			/* f.e.:
				bla = TEXT	# <- This combination
				bla. = .... # <- 
			*/

			
			$isCObjectConfiguration = false;
			$potentialCObjectParentKey = substr((string) $key, 0, -1);
			if($isSubConfiguration && array_key_exists($potentialCObjectParentKey, $conf) && is_string($conf[$potentialCObjectParentKey])) {
				$potentialCObjectName = trim($conf[$potentialCObjectParentKey]);
				$isCObjectConfiguration = 
					(GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() < 12) ?
					in_array($potentialCObjectName, $availableCObjects) : ($cObj->getContentObject($potentialCObjectName) !== null);
			}

			// f.e. "=< lib.content"
			$isReference = (is_string($c) && str_starts_with((string) $c, '<'));			

			if ($isReference) {
                $referenceKey = trim(substr($c, 1));
                $config = array_replace_recursive(self::get($referenceKey), $conf[$key . '.'] ?? []);
                $conf[$key] = static::populateTypoScriptConfiguration($config, $cObj, $maxNesting - 1);
            } elseif ($isCObjectConfiguration) {
                $conf[$potentialCObjectParentKey] = $cObj->cObjGetSingle($conf[$potentialCObjectParentKey], $c);
                // Delete Subinformations, because they rendered in the parent
                unset($conf[$key]);
            } elseif ($isSubConfiguration) {
                // when no Parent exist save the values and remove the . at the end (just a subconfiguration, for parent/child cObjects, see above)		
                $parentKey = substr((string) $key, 0, -1);
                $conf[$parentKey] = static::populateTypoScriptConfiguration($c,$cObj, $maxNesting - 1);
                unset($conf[$key]);
            } elseif (is_array($c)) {
                // resolve arrays and check for subconfigurations
                $conf[$key] = static::populateTypoScriptConfiguration($c,$cObj, $maxNesting - 1);
            }
		}

		return $conf;
	}
}
