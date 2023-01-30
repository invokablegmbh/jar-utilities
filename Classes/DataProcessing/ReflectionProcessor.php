<?php

declare(strict_types=1);

namespace Jar\Utilities\DataProcessing;

use Jar\Utilities\Services\ReflectionService;
use Jar\Utilities\Utilities\TypoScriptUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
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
 *         finisher {
 *             field {
 *                10 = \TYPO3\Bla\Blub->handleSth
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
        $populatedProcessorConfiguration = TypoScriptUtility::populateTypoScriptConfiguration($processorConfiguration, $cObj);        
        $table = $populatedProcessorConfiguration['table'] ?? $processedData['table'] ?? 'tt_content';
        $row = $populatedProcessorConfiguration['row'] ?? $processedData['data'] ?? $processedData;
        $maxDepth = $populatedProcessorConfiguration['maxDepth'] ?? 8;

        $reflectionService = GeneralUtility::makeInstance(ReflectionService::class);        
        $reflectionService->setPropertiesByConfigurationArray($populatedProcessorConfiguration);

        // special case: when $processedData has the property "rows" use that instead and handle the whole list (performance boost by nested DataProcessors) 
        $singleRowMode = !key_exists('rows', $processedData);
        if($singleRowMode) {
            $result = reset($reflectionService->buildArrayByRows([$row], $table, $maxDepth));            
        } else {
            $result = $reflectionService->buildArrayByRows($processedData['rows'] ?? [], $table, $maxDepth);            
        }

        // handle nested dataprocessors
        if (!empty($processorConfiguration['dataProcessing.'])) {
            $contentDataProcessor = GeneralUtility::makeInstance(ContentDataProcessor::class);
            $recordContentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);            
            foreach ($result as $key => $item) {                
                $recordContentObjectRenderer->start($item, $table);
                $result[$key] = $contentDataProcessor->process($recordContentObjectRenderer, $processorConfiguration, $item);
            }
        }        

        if (!empty($populatedProcessorConfiguration['as'])) {
            $processedData[$populatedProcessorConfiguration['as']] = $result;
        } else {
            if($singleRowMode) {
                if((bool) ($populatedProcessorConfiguration['replace'] ?? false)) {
                    $processedData = $result;
                } else {
                    ArrayUtility::mergeRecursiveWithOverrule($processedData, $result);            
                }
            } else {
                $processedData = $result;
            }
        }

        return $processedData;
    }


    
}
