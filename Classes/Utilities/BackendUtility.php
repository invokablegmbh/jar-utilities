<?php

declare(strict_types=1);

namespace Jar\Utilities\Utilities;

use InvalidArgumentException;
use Jar\Utilities\Services\RegistryService;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility as BackendUtilityCore;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */


/** 
 * @package Jar\Utilities\Utilities 
 * Collection of helpers for backend developing.
 **/

class BackendUtility
{

	/**
	 * Creates a frontend link, in backend context.
	 * 
	 * @param int $pageUid The page uid.
	 * @param array $params Typolink parameters.	 
	 * @return string Link url.
	 */
	public static function createFrontendLink(int $pageUid = 1, array $params = []): string
	{
		if (empty($GLOBALS['TSFE'])) {
			$finder = GeneralUtility::makeInstance(SiteFinder::class);
			$site = $finder->getSiteByPageId($pageUid);
			$router = $site->getRouter();
			return (string)$router->generateUri($pageUid, $params);
		}
		$cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);

		$linkConf = [
			'parameter' => $pageUid,
			'forceAbsoluteUrl' => 0,
			'additionalParams' => GeneralUtility::implodeArrayForUrl(NULL, $params),
			'linkAccessRestrictedPages' => 1
		];

		$link = $cObj->typolink_URL($linkConf);

		return $link;
	}

	/**
	 * Returns the current page uid (in backend and frontend context).
	 * 
	 * @return int Current page uid.
	 * */
	public static function currentPageUid(): ?int
	{
		if(!array_key_exists('TYPO3_REQUEST', $GLOBALS) || !$GLOBALS['TYPO3_REQUEST']) {
			return null;

			if (!empty(GeneralUtility::_GP('id'))) {
				return (int) GeneralUtility::_GP('id');
			}
		}

		if($GLOBALS['TYPO3_REQUEST'] 
			&& $GLOBALS['TYPO3_REQUEST']->getAttribute('route') 
			&& $GLOBALS['TYPO3_REQUEST']->getAttribute('route')->getOption('moduleName') 
			&& strpos($GLOBALS['TYPO3_REQUEST']->getAttribute('route')->getOption('moduleName'), 'file_') !==  false) {
			return null;
		}

		if (!empty(GeneralUtility::_GP('id'))) {
			return (int) GeneralUtility::_GP('id');
		}

		if (!empty(GeneralUtility::_GP('returnUrl'))) {			
			parse_str(end(explode('?', GeneralUtility::_GP('returnUrl'))), $output);
			if (!empty($output['id'])) {
				return (int) $output['id'];
			}
		}

		if(isset($GLOBALS['TSFE'])) {
			return (int) $GLOBALS['TSFE']->id;
		}

		return null;
	}


	/**
	 * Get the fully-qualified domain name of the host.
	 *
	 * @return string The fully-qualified host name.
	 */
	public static function getHostname(): string
	{
		$host = '';
		// If not called from the command-line, resolve on getIndpEnv()
		if (!Environment::isCli()) {
			$host = GeneralUtility::getIndpEnv('HTTP_HOST');
		}
		if (!$host) {
			// will fail for PHP 4.1 and 4.2
			$host = @php_uname('n');
			// 'n' is ignored in broken installations
			if (strpos($host, ' ')) {
				$host = '';
			}
		}
		// We have not found a FQDN yet
		if ($host && strpos($host, '.') === false) {
			$ip = gethostbyname($host);
			// We got an IP address
			if ($ip != $host) {
				$fqdn = gethostbyaddr($ip);
				if ($ip != $fqdn) {
					$host = $fqdn;
				}
			}
		}
		if (!$host) {
			$host = 'localhost.localdomain';
		}
		return $host;
	}


	/**
	 * Get route link for editing records in backend.
	 * 
	 * @param string $table The record table.
	 * @param string $uid The record uid.
	 * @return string Link The resulting link.
	 */
	public static function getEditLink(string $table, int $uid): string
	{
		$editLink = '';
		$record = BackendUtilityCore::getRecord($table, $uid);

		$localCalcPerms = $GLOBALS['BE_USER']->calcPerms($record);
		$permsEdit = $localCalcPerms & ($table == 'tt_content' ? Permission::CONTENT_EDIT : Permission::PAGE_EDIT);

		if ($permsEdit) {
			$uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
			$returnUrl = $uriBuilder->buildUriFromRoute('web_layout', ['id' => static::currentPageUid()])  . ('#element-' . $table . '-' . $uid);

			$editLink = $uriBuilder->buildUriFromRoute('record_edit', [
				'returnUrl' => $returnUrl . '',
				'edit[' . $table . '][' . $uid . ']' => 'edit',
			]);
		}

		return (string) $editLink;
	}


	/**
	 * Get route link for editing records in backend. Wrapped in a <a>-Tag
	 * @param string $table The record table.
	 * @param int $uid The record uid.
	 * @param string $content Inner HTML of the <a>-tag.
	 * @return string Link The resulting <a>-tag.
	 */
	public static function getWrappedEditLink(string $table, int $uid, string $content): string
	{
		$editLink = static::getEditLink($table, $uid);

		return empty($editLink) ? $content : '<a href="' . $editLink . '">' . $content . '</a>';
	}



	/**
	 * Returns informations from the "New Content Wizard".
	 * f.e. "getWizardInformations('html')"
	 * [
	 *       iconIdentifier => 'content-special-html',
	 *       title => 'Plain HTML',
	 *       description => 'With this element you can insert raw HTML code on the page.'
	 *       ...
	 * ]
	 * 
	 * @param string $ctype The CType.
	 * @return array Informations about that wizard.
	 * @throws InvalidArgumentException 
	 * @throws TooDirtyException 
	 * @throws ReflectionException 
	 */
	public static function getWizardInformations(string $ctype): array
	{
		$cache = GeneralUtility::makeInstance(RegistryService::class);
		$hash = 'all-wizard-elements';

		if (($elements = $cache->get('backend-utility', $hash)) === false) {

			$elements = IteratorUtility::map(
				IteratorUtility::flatten(
					IteratorUtility::pluck(
						TypoScriptUtility::convertTypoScriptArrayToPlainArray(
							BackendUtilityCore::getPagesTSconfig(BackendUtility::currentPageUid())['mod.']['wizards.']['newContentElement.']['wizardItems.'] ?? []
						),
						'elements'
					)
				),
				function ($element) {
					$element['title'] = LocalizationUtility::localize($element['title']);
					$element['description'] = LocalizationUtility::localize($element['description']);
					return $element;
				}
			);

			$cache->set('backend-utility', $hash, $elements);
		}
		return $elements[$ctype];
	}


	/**
	 * Returns the current page TSconfig as array.
	 * 
	 * @return array Current page TSconfig.
	 * @throws InvalidArgumentException 
	 */
	public static function getCurrentPageTS(): array {
		$cache = GeneralUtility::makeInstance(RegistryService::class);
		$currentPageUid = BackendUtility::currentPageUid();
		$hash = 'current-page-ts-' . $currentPageUid;

		if (($pageTs = $cache->get('backend-utility', $hash)) === false) {
			$pageTs = TypoScriptUtility::convertTypoScriptArrayToPlainArray(
				BackendUtilityCore::getPagesTSconfig($currentPageUid)
			);
			$cache->set('backend-utility', $hash, $pageTs);
		}

		return $pageTs;
	}
}
