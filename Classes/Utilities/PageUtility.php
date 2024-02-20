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
 * Doing Page (and Pagetree) related stuff.
 **/

class PageUtility
{
    /**
     * Returns all Sub-Pids of certain PIDs.
     * 
     * @param string $pids The starting PID.
     * @param int $level Depth of the traversing levels.
     * @return array List of matching PIDs.
     * @throws InvalidArgumentException 
     */
    public static function getPidsRecursive(string $pids, int $level = 3): array
    {
        $pidList = [];
        $pids = GeneralUtility::trimExplode(',', $pids);
        foreach ($pids as $pid) {
            $pidList = array_merge($pidList, explode(',', (string)static::getTreeList($pid, $level, 0, 1)));
        }

        return array_unique($pidList);
    }

    /**
     * Recursively fetch all descendants of a given page
     *
     * @param int $id uid of the page
     * @param int $depth
     * @param int $begin
     * @param string $permClause
     * @return string comma separated list of descendant pages
     */
    public static function getTreeList($id, $depth, $begin = 0, $permClause = '')
    {
        $depth = (int)$depth;
        $begin = (int)$begin;
        $id = (int)$id;
        if ($id < 0) {
            $id = abs($id);
        }
        if ($begin == 0) {
            $theList = (string)$id;
        } else {
            $theList = '';
        }
        if ($id && $depth > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $queryBuilder->select('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('sys_language_uid', 0)
                )
                ->orderBy('uid');
            if ($permClause !== '') {
                $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($permClause));
            }
            $statement = $queryBuilder->executeQuery();
            while ($row = $statement->fetchAssociative()) {
                if ($begin <= 0) {
                    $theList .= ',' . $row['uid'];
                }
                if ($depth > 1) {
                    $theSubList = static::getTreeList($row['uid'], $depth - 1, $begin - 1, $permClause);
                    if (!empty($theList) && !empty($theSubList) && ($theSubList[0] !== ',')) {
                        $theList .= ',';
                    }
                    $theList .= $theSubList;
                }
            }
        }
        return $theList;
    }

    /**
     * Slides up a the Pagetree and return the nearest filled value of the field.
     * 
     * @param string $fieldname Name of the field/column.
     * @return string|null Value of the field when found, otherwise "null".
     */
    public static function getPageFieldSlided(string $fieldname): ?string
    {
        return reset(IteratorUtility::compact(IteratorUtility::pluck($GLOBALS['TSFE']->rootLine, $fieldname)));
    }
}
