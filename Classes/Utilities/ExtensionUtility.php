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
 * Load informations from TYPO3 extensions.
 **/


class ExtensionUtility
{

	/**
	 * Loads the configuration from a extension.
	 * @param string $extkey The extension key.
	 * @throws Exception
	 * @return array The extension configuration.
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
	 * Get the absolute path to a extension.
	 * @param string $extkey The extension key.
	 * @param string $path Optional path in extension directory.
	 * @return string The absolute path.
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
	protected static function getExtensionKey(string $qualifiedExtensionName): string
	{
		list(, $extensionKey) = static::getVendorNameAndExtensionKey($qualifiedExtensionName);
		return $extensionKey;
	}


	/**
	 * @param string $qualifiedExtensionName
	 * @return array
	 */
	protected static function getVendorNameAndExtensionKey(string $qualifiedExtensionName): array
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
	protected static function hasVendorName(string $qualifiedExtensionName): bool
	{
		return false !== strpos($qualifiedExtensionName, '.');
	}

	/**
	 * Retrieves the version of an installed extension.
	 * If the extension is not installed, this function returns an empty string.
	 * Same as ExtensionManagementUtility::getExtensionVersion but removes trailing "v".
	 * Handy when using version_compare.
	 *
	 * @param string $extkey The extension key.
	 * @return string The extension version as a string in the format "x.y.z".
	 */
	public static function getExtensionVersion(string $extkey): string
	{
		$result = strtolower(ExtensionManagementUtility::getExtensionVersion($extkey));
		if(strpos($result, 'v') === 0) {
			$result = substr($result, 1);
		}
		return $result;
	}
}
