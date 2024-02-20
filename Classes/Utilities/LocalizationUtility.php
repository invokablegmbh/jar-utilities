<?php

declare(strict_types=1);

namespace Jar\Utilities\Utilities;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility as CoreLocalizationUtility;

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */


/** 
 * @package Jar\Utilities\Utilities
 * Shorthands for receiving and output translations. 
 **/

class LocalizationUtility
{
    /**
     * Loads the translations, set by _LOCAL_LANG from a extension.
     * 
     * @param string $extension Extension Key without the beginnining "tx_"
     * @return array The translations.     
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
     * Get the current Language Service.
     * 
     * @return LanguageService The language Service.
     */
    public static function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'] ?? GeneralUtility::makeInstance(LanguageService::class);
    }

    /**
     * Localize a translation key to the translation value.
     * 
     * @param string $input The translation key.
     * @return string The translation value or the translation key, when no translation is found.
     */
    public static function localize(?string $input): string {
        if(empty($input)) {
            return '';
        }
        return self::getLanguageService()->sL($input) ?? $input;
    }
}
