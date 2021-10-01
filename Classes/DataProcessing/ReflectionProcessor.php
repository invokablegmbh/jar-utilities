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
 *     dataProcessing.10 = Jar\Utilities\DataProcessing\ReflectionProcessor
 *     dataProcessing.10 {
 *         table = tt_content
 *         tableColumnBlacklist {
 *             tt_content = space_*_class
 *         }
 *         tableColumnWhitelist {
 *             tt_content = feditorce_utility_card_*
 *         }
 *         tableColumnRemoveablePrefixes {
 *             tt_content = feditorce_utility_card_
 *         }
 *         tableColumnRemapping {
 *             tt_content {
 *                 image = heroimage
 *             }
 *         }
 *         buildingConfiguration {
 *             file {
 *                 showDetailedInformations = 0
 *                 processingConfigurationForCrop {
 *                     desktop.maxWidth = 3000
 *                     medium.maxWidth = 1920
 *                     tablet.maxWidth = 1024
 *                     mobile.maxWidth = 920
 *                 }
 *             }
 *         }
 *     }
 * }
 */

/** @package Jar\Utilities\DataProcessing */
class ReflectionProcessor implements DataProcessorInterface
{
    /**
     * Process data to complex objects and convert them to a simple Array structure based of TCA Configuration
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

        $table = $processorConfiguration['table'] ?? 'tt_content';
        $row = $processorConfiguration['row'] ?? $processedData['data'] ?? $processedData;
        $maxDepth = $processorConfiguration['maxDepth'] ?? 8;

        $reflectionService = GeneralUtility::makeInstance(ReflectionService::class);

        $tableColumnBlacklist = $this->convertProcessorConfigurationStringListToArray($processorConfiguration, 'tableColumnBlacklist');
        if (!empty($tableColumnBlacklist)) {
            $reflectionService->addToTableColumnBlacklist($tableColumnBlacklist);
        }

        $tableColumnWhitelist = $this->convertProcessorConfigurationStringListToArray($processorConfiguration, 'tableColumnWhitelist');
        if (!empty($tableColumnWhitelist)) {
            $reflectionService->setTableColumnWhitelist($tableColumnWhitelist);
        }

        $tableColumnRemoveablePrefixes = $this->convertProcessorConfigurationStringListToArray($processorConfiguration, 'tableColumnRemoveablePrefixes');
        if (!empty($tableColumnRemoveablePrefixes)) {
            $reflectionService->setTableColumnRemoveablePrefixes($tableColumnRemoveablePrefixes);
        }

        if (!empty($processorConfiguration['tableColumnRemapping']) && is_array($processorConfiguration['tableColumnRemapping'])) {
            $reflectionService->setTableColumnRemapping($processorConfiguration['tableColumnRemapping']);
        }

        if (!empty($processorConfiguration['buildingConfiguration']) && is_array($processorConfiguration['buildingConfiguration'])) {
            $reflectionService->setBuildingConfiguration($processorConfiguration['buildingConfiguration']);
        }


        $result = $reflectionService->buildArrayByRow($row, $table, $maxDepth);

        if (!empty($processorConfiguration['as'])) {
            $processedData[$processorConfiguration['as']] = $result;
        } else {
            ArrayUtility::mergeRecursiveWithOverrule($processedData, $result);
        }

        return $processedData;
    }


    /**
     * @param array $processorConfiguration 
     * @param string $key 
     * @return array 
     */
    private function convertProcessorConfigurationStringListToArray(array $processorConfiguration, string $key): array
    {
        $result = [];
        if (!empty($processorConfiguration[$key]) && is_array($processorConfiguration[$key])) {
            $result = $processorConfiguration[$key];
            foreach ($result as $table => $list) {
                $result[$table] = GeneralUtility::trimExplode(',', $list);
            }
        }
        return $result;
    }
}
