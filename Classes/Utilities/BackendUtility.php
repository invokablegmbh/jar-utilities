<?php

declare(strict_types=1);

namespace Jar\Utilities\Utilities;

use InvalidArgumentException;
use Jar\Utilities\Services\RegistryService;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility as BackendUtilityCore;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */


/** 
 * @package Jar\Utilities\Utilities 
 * Collection of Helpers for Backend Developing
 **/

class BackendUtility
{

	/**
	 * Creates a Frontend Link, in Backend Context     
	 * @param int $pageUid
	 * @param array $params	 
	 * @return string
	 */
	public static function createFrontendLink(int $pageUid = 1, array $params = []): array
	{
		if (empty($GLOBALS['TSFE'])) {
			static::initFrontend();
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
	 * Init Frontend Structure (f.e. for Link Generating in CLI-Commands)
	 * @return void 
	 * @throws InvalidArgumentException 
	 */
	protected static function initFrontend(): void
	{
		$id = 1;
		$typeNum = 0;
		if (!is_object($GLOBALS['TT'])) {
			$GLOBALS['TT'] = new \TYPO3\CMS\Core\TimeTracker\TimeTracker;
			$GLOBALS['TT']->start();
		}
		$GLOBALS['TSFE'] = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class, $GLOBALS['TYPO3_CONF_VARS'], $id, $typeNum);
		$GLOBALS['TSFE']->connectToDB();
		$GLOBALS['TSFE']->initFEuser();
		$GLOBALS['TSFE']->determineId();
		$GLOBALS['TSFE']->initTemplate();
		$GLOBALS['TSFE']->getConfigArray();
	}



	/**
	 * Returns the current PageUid 
	 * @return int  
	 * */
	public static function currentPageUid(): int
	{

		if (!empty(GeneralUtility::_GP('id'))) {
			return (int) GeneralUtility::_GP('id');
		}

		if (!empty(GeneralUtility::_GP('returnUrl'))) {
			parse_str(end(explode('?', GeneralUtility::_GP('returnUrl'))), $output);
			if (!empty($output['id'])) {
				return (int) $output['id'];
			}
		}
		return (int) $GLOBALS['TSFE']->id;
	}


	/**
	 * Get the fully-qualified domain name of the host.
	 *
	 * @param bool $requestHost Use request host (when not in CLI mode).
	 * @return string The fully-qualified host name.
	 */
	public static function getHostname(bool $requestHost = true): string
	{
		$host = '';
		// If not called from the command-line, resolve on getIndpEnv()
		if ($requestHost && !Environment::isCli()) {
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
	 * get Backend Routing Link
	 * @param string $table
	 * @param string $uid
	 * @return string Link 
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
	 * get Backend Routing Link, ready with <a ...>$content</a>
	 * @param string $table
	 * @param int $uid
	 * @param string $content
	 * @return string Link 
	 */
	public static function getWrappedEditLink(string $table, int $uid, string $content): string
	{
		$editLink = static::getEditLink($table, $uid);

		return empty($editLink) ? $content : '<a href="' . $editLink . '">' . $content . '</a>';
	}



	/**
	 * returns informations from the "New Content Wizard"
	 * f.e. "getWizardInformations('html')"
	 * [
	 *       iconIdentifier => 'content-special-html',
	 *       title => 'Plain HTML',
	 *       description => 'With this element you can insert raw HTML code on the page.'
	 *       ...
	 * ]
	 * 
	 * @param string $ctype 
	 * @return array 
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
	 * @return array 
	 * @throws InvalidArgumentException 
	 */
	public static function getCurrentPageTS(): array {
		$cache = GeneralUtility::makeInstance(RegistryService::class);
		$hash = 'current-page-ts';

		if (($pageTs = $cache->get('backend-utility', $hash)) === false) {
			$pageTs = TypoScriptUtility::convertTypoScriptArrayToPlainArray(
				BackendUtilityCore::getPagesTSconfig(BackendUtility::currentPageUid())
			);
			$cache->set('backend-utility', $hash, $pageTs);
		}

		return $pageTs;
	}
}
