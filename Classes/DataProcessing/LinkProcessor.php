<?php

declare(strict_types=1);

namespace Jar\Utilities\DataProcessing;

use Jar\Utilities\Services\ReflectionService;
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
 * 20 = Jar\Utilities\DataProcessing\LinkProcessor
 * 20 {
 *     page = {$plugin.tx_j77template.settings.pageUid.root}
 *     title = TEXT
 *     title.data = field:titel
 *     class = warning blink
 *     target = _blank
 *     as = detail_link
 *     params {
 *         item_uid = TEXT
 *         item_uid.data = field:uid
 *         shortview = 1
 *     }
 * }
 */

/** @package Jar\Utilities\DataProcessing */
class LinkProcessor implements DataProcessorInterface
{
    /**
     * Process data to create a link on a dataprocessed element
     *
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array $contentObjectConfiguration The configuration of Content Object
     * @param array $processorConfiguration The configuration of this processor
     * @param array $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     * @return array the processed data as key/value store
     */
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData)
    {
        $linkConfiguration = [
            'page' => 0,
            'target' => '-',
            'class' => '-',
            'title' => '-',
        ];

        $populatedProcessorConfiguration = TypoScriptUtility::populateTypoScriptConfiguration($processorConfiguration, $cObj);

        ArrayUtility::mergeRecursiveWithOverrule($linkConfiguration, $populatedProcessorConfiguration);
        $stringKeys = ['target', 'class', 'title'];
        foreach ($linkConfiguration as $key => $value) {
            if (in_array($key, $stringKeys) && $value !== '-' && !empty($value)) {
                $linkConfiguration[$key] = '"' . $value . '"';
            }
        }

        $paramQuery = http_build_query($linkConfiguration['params'] ?? []);
        $linkString = 't3://page?uid=' . $linkConfiguration['page'] . (empty($paramQuery) ? '' : '&' . $paramQuery) . ' ' . $linkConfiguration['target'] . ' ' . $linkConfiguration['class'] . ' ' . $linkConfiguration['title'];

        $key = $populatedProcessorConfiguration['as'] ?? 'link';
        $processedData[$key] = FormatUtility::buildLinkArray($linkString);

        return $processedData;
    }
}
