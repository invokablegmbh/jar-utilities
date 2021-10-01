<?php

declare(strict_types=1);

namespace Jar\Utilities\Utilities;

use BadFunctionCallException;
use Jar\Utilities\Services\RegistryService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */


/** 
 * @package Jar\Utilities\Utilities 
 **/


class ExtensionUtility
{

	/**
	 * @param string $extkey 
	 * @throws Exception
	 * @return array
	 */
	public static function getExtensionConfiguration(string $extkey): array
	{
		$cache = GeneralUtility::makeInstance(RegistryService::class);

		$hash = $extkey;

		if (($configuration = $cache->get('extensionConfiguration', $hash)) === false) {
			$configuration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get($extkey);
			$cache->set('extensionConfiguration', $hash, $configuration);
		}

		return $configuration ?? [];
	}


	/**
	 * @param string $extkey 
	 * @param string $path 
	 * @return string 
	 * @throws BadFunctionCallException 
	 */
	public static function getAbsExtPath(string $extkey, string $path): string
	{
		$legacyKey = static::getExtensionKey($extkey);
		$templateRootPath = ExtensionManagementUtility::extPath($legacyKey, $path);

		return $templateRootPath;
	}


	/**
	 * @param string $qualifiedExtensionName
	 * @return string
	 */
	public static function getExtensionKey(string $qualifiedExtensionName): string
	{
		list(, $extensionKey) = static::getVendorNameAndExtensionKey($qualifiedExtensionName);
		return $extensionKey;
	}


	/**
	 * @param string $qualifiedExtensionName
	 * @return array
	 */
	public static function getVendorNameAndExtensionKey(string $qualifiedExtensionName): array
	{
		static $cache = [];
		if (isset($cache[$qualifiedExtensionName])) {
			return $cache[$qualifiedExtensionName];
		}
		if (true === static::hasVendorName($qualifiedExtensionName)) {
			list($vendorName, $extensionKey) = GeneralUtility::trimExplode('.', $qualifiedExtensionName);
		} else {
			$vendorName = null;
			$extensionKey = $qualifiedExtensionName;
		}
		$extensionKey = GeneralUtility::camelCaseToLowerCaseUnderscored($extensionKey);
		$cache[$qualifiedExtensionName] = [$vendorName, $extensionKey];
		return [$vendorName, $extensionKey];
	}


	/**
	 * @param string $qualifiedExtensionName
	 * @return bool
	 */
	public static function hasVendorName(string $qualifiedExtensionName): bool
	{
		return false !== strpos($qualifiedExtensionName, '.');
	}
}
