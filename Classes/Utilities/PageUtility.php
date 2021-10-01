<?php

declare(strict_types=1);

namespace Jar\Utilities\Utilities;

use Jar\Utilities\Utilities\IteratorUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */


/** 
 * @package Jar\Utilities\Utilities 
 **/

class PageUtility
{
    /**
     * returns all Sub-Pids of certain PIDs
     * @param string $pids 
     * @param int $level 
     * @return array 
     * @throws InvalidArgumentException 
     */
    public static function getPidsRecursive(string $pids, int $level = 3): array
    {
        $pidList = [];
        $queryGenerator = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\QueryGenerator::class);
        $pids = GeneralUtility::trimExplode(',', $pids);
        foreach ($pids as $pid) {
            $pidList = array_merge($pidList, explode(',', (string)$queryGenerator->getTreeList($pid, $level, 0, 1)));
        }

        return array_unique($pidList);
    }



    /**
     * Slides up a the Pagetree and return the nearest filled value of $fieldname
     * @param string $fieldname 
     * @return string 
     */
    public static function getPageFieldSlided(string $fieldname): string
    {
        return reset(IteratorUtility::compact(IteratorUtility::pluck($GLOBALS['TSFE']->rootLine, $fieldname)));
    }
}
