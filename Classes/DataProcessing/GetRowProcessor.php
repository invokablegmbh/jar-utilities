<?php

declare(strict_types=1);

namespace Jar\Utilities\DataProcessing;

use Jar\Utilities\Services\ReflectionService;
use Jar\Utilities\Utilities\DataUtility;
use Jar\Utilities\Utilities\FormatUtility;
use Jar\Utilities\Utilities\StringUtility;
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
 * 20 = Jar\Utilities\DataProcessing\GetRowProcessor
 * 20 {
 *     table = tt_content
 *     uid = 20
 * }
 */

/** @package Jar\Utilities\DataProcessing */
class GetRowProcessor implements DataProcessorInterface
{
    /**
     * Shorthand for Loading just one element, if you want to load multiple items use TYPO3\CMS\Frontend\DataProcessing\DatabaseQueryProcessor instead
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

        $result = [];
        if(!empty($table = $populatedProcessorConfiguration['table']) && !empty($uid = (int) $populatedProcessorConfiguration['uid'])) {
            $result = DataUtility::getRow($table, $uid);
            
            // handle nested dataprocessors
            if (!empty($processorConfiguration['dataProcessing.'])) {
                $contentDataProcessor = GeneralUtility::makeInstance(ContentDataProcessor::class);
                $recordContentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
                $recordContentObjectRenderer->start($result, $table);
                $result = $contentDataProcessor->process($recordContentObjectRenderer, $processorConfiguration, $result);
            }
        }
        
        if (!empty($populatedProcessorConfiguration['as'])) {
            if (!isset($processedData[$processorConfiguration['as']]) || !is_array($processedData[$processorConfiguration['as']])) {
                $processedData[$processorConfiguration['as']] = [];
            }
            ArrayUtility::mergeRecursiveWithOverrule($processedData[$processorConfiguration['as']], $result);
        } else {
            ArrayUtility::mergeRecursiveWithOverrule($processedData, $result);
        }

        return $processedData;
    }
}
