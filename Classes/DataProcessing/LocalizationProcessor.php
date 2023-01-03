<?php

declare(strict_types=1);

namespace Jar\Utilities\DataProcessing;

use Jar\Utilities\Services\ReflectionService;
use Jar\Utilities\Utilities\LocalizationUtility;
use Jar\Utilities\Utilities\TypoScriptUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 * 
 * Example:
 * tt_content.ctype = FLUIDTEMPLATE
 * tt_content.ctype {
 *     template = TEXT
 *     template.value = <h1>HALLO</h1>{_all -> f:debug()}
 *     dataProcessing {
 *          20 = Jar\Utilities\DataProcessing\LocalizationProcessor
 *          20 {
 *              extensionsToLoad = j77template
 *              as = translations
 *              # if flat = 1, all translations will be merged into one big list, otherwise they will be grouped by extension
 *              flat = 0
 *          }
 *     }
 * }
 * 
 */

/** @package Jar\Utilities\DataProcessing */
class LocalizationProcessor implements DataProcessorInterface
{
    /**
     * Add translations directly to the template - this gives frontenders faster handling of the translation variables
     *
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array $contentObjectConfiguration The configuration of Content Object
     * @param array $processorConfiguration The configuration of this processor
     * @param array $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     * @return array the processed data as key/value store
     */
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData)
    {
        $processorConfiguration = TypoScriptUtility::populateTypoScriptConfiguration($processorConfiguration);

        $result = [];

        if (!empty($processorConfiguration['extensionsToLoad'])) {
            $extensionsToLoad = GeneralUtility::trimExplode(',', $processorConfiguration['extensionsToLoad']);
            if (!empty($extensionsToLoad)) {
                $localizationUtility = GeneralUtility::makeInstance(LocalizationUtility::class);
                foreach ($extensionsToLoad as $extension) {
                    //if flat = 1, all translations will be merged into one big list, otherwise they will be grouped by extension
                    if(!!$processorConfiguration['flat']) {
                        ArrayUtility::mergeRecursiveWithOverrule($result, $localizationUtility->loadTyposcriptTranslations($extension));
                    } else {
                        $result[$extension] = $localizationUtility->loadTyposcriptTranslations($extension);
                    }
                }
            }
        }

        if (!empty($processorConfiguration['as'])) {
            if(!isset($processedData[$processorConfiguration['as']]) ) {
                $processedData[$processorConfiguration['as']] = [];
            }
            ArrayUtility::mergeRecursiveWithOverrule($processedData[$processorConfiguration['as']], $result);
        } else {
            ArrayUtility::mergeRecursiveWithOverrule($processedData, $result);
        }

        return $processedData;
    }
}
