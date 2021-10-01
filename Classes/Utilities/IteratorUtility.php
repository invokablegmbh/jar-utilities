<?php

declare(strict_types=1);

namespace Jar\Utilities\Utilities;

use Closure;

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */


/** 
 * @package Jar\Utilities\Utilities 
 * 
 **/

class IteratorUtility
{

    /**
     * @param array $arr 
     * @param string $col 
     * @param int $dir 
     * @return void 
     */
    public static function sortArrayByColumn(array &$arr, string $col, int $dir = SORT_ASC): void
    {
        $sort_col = array();
        foreach ($arr as $key => $row) {
            $sort_col[$key] = $row[$col];
        }
        array_multisort($sort_col, $dir, $arr);
    }


    /**
     * @param array $listOfObjects 
     * @param string $methodName 
     * @return array 
     */
    public static function extractValuesViaGetMethod(array $listOfObjects, string $methodName): array
    {
        if (empty($listOfObjects)) {
            return [];
        }

        $result = array();
        $method = 'get' . ucfirst($methodName);
        foreach ($listOfObjects as $o) {
            if (method_exists($o, $method)) {
                $result[] = $o->$method();
            }
        }
        return $result;
    }

    /**
     * @param array $listOfObjects 
     * @param string $methodName 
     * @param bool $keepKeys 
     * @return array 
     */
    public static function extractValuesViaGetMethodFlattened(array $listOfObjects, string $methodName, bool $keepKeys = false): array
    {
        $result = [];
        foreach (static::extractValuesViaGetMethod($listOfObjects, $methodName) as $r) {
            if (!$keepKeys) {
                $r = array_values($r);
            }
            $result = array_merge($result, $r);
        }
        return $result;
    }

    /**
     * @param array $listOfObjects 
     * @param string $method 
     * @return array 
     */
    public static function callMethod(array $listOfObjects, string $method): array
    {
        $result = [];
        $listOfObjects = is_array($listOfObjects) ? $listOfObjects : [];
        foreach ($listOfObjects as $o) {
            if (method_exists($o, $method)) {
                $result[] = $o->$method();
            }
        }
        return $result;
    }



    /**
     * @param null|array $arr 
     * @return array 
     */
    public static function compact(?array $arr): array
    {
        return array_filter(is_array($arr) ? $arr : []);
    }


    /**
     * @param array $arr 
     * @return array 
     */
    public static function flatten(array $arr): array
    {
        $result = [];
        foreach ($arr as $a) {
            if (is_array($a)) {
                $result = array_merge($result, $a);
            } else {
                $result[] = $a;
            }
        }
        return $result;
    }

    /**
     * @param array $arr 
     * @param callable $func 
     * @return array 
     */
    public static function filter(array $arr, callable $func): array
    {
        $result = [];
        foreach ($arr as $k => $a) {
            if ($func($a, $k, $arr)) {
                $result[] = $a;
            }
        }
        return $result;
    }


    /**
     * @param array $arr 
     * @param callable $func 
     * @return array 
     */
    public static function map(array $arr, callable $func): array
    {
        $result = [];
        foreach ($arr as $k => $a) {
            $result[$k] = $func($a, $k, $result);
        }
        return $result;
    }


    /**
     * @param array $arr 
     * @return array 
     */
    public static function first(array $arr): array
    {
        return reset($arr);
    }


    /**
     * @param array $arr 
     * @param string $key 
     * @return array 
     */
    public static function pluck(array $arr, string $key): array
    {
        return array_column($arr, $key);
    }


    /**
     * @param array $arr 
     * @param string $needle 
     * @return bool 
     */
    public static function contains(array $arr, string $needle): bool
    {
        return in_array($needle, is_array($arr) ? $arr : []);
    }

    /**
     * Returns only array entries listed in a whitelist
     *
     * @param array $array original array to operate on
     * @param array $whitelist keys you want to keep
     * @return array
     */
    public static function whitelist(array $array, array $whitelist): array
    {
        return array_intersect_key(
            $array,
            array_flip($whitelist)
        );
    }

    /**
     * Returns only array entries listed in a whitelist
     *
     * @param array $array List of array items
     * @param array $whitelist keys you want to keep
     * @return array
     */
    public static function whitelistList(array $array, array $whitelist): array
    {
        return static::map($array, function ($a) use ($whitelist) {
            return static::whitelist($a, $whitelist);
        });
    }


    /**
     *
     * @param array $array List of array items
     * @param string $whitelist keys you want to keep
     * @return array
     */
    public static function indexBy(array $arr, array $key): array
    {
        $r = [];
        foreach ($arr as $a) {
            $r[$a[$key]] = $a;
        }
        return $r;
    }
}
