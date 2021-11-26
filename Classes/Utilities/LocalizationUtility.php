<?php

declare(strict_types=1);

namespace Jar\Utilities\Utilities;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility as CoreLocalizationUtility;

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */


/** 
 * @package Jar\Utilities\Utilities 
 **/

class LocalizationUtility
{
    /**
     * @param string $extension
     * @param string $keys
     * @throws Exception
     */
    public static function loadTyposcriptTranslations(string $extension): array
    {
        $result = [];
        $ts = TypoScriptUtility::get('plugin.tx_' . strtolower($extension) . '._LOCAL_LANG') ?? [];
        $keys = [];
        foreach ($ts as $langTs) {
            $keys = array_unique(array_merge($keys, array_keys($langTs)));
        }

        foreach ($keys as $key) {
            $result[$key] = CoreLocalizationUtility::translate($key, $extension);
        }
        
        return $result;
    }


    /**
     * @return LanguageService
     */
    public static function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'] ?? GeneralUtility::makeInstance(LanguageService::class);
    }

    /**
     * @param string $input 
     * @param bool $withFallback returns the original $input when no translation is found
     * @return void 
     */
    public static function localize(string $input, bool $withFallback = true) {
        return self::getLanguageService()->sL($input) ?? $input;
    }
}
