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
 * Helpers for handling and iterating throught lists.
 **/

class IteratorUtility
{

    /**
     * Sort array by column/key value.
     * 
     * @param array $arr Reference of array.
     * @param string $col The column/key name.
     * @param int $dir The direction SORT_ASC or SORT_DESC 
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
     * Extracts properties from objects via their get method.
     * 
     * @param array $listOfObjects List of objects.
     * @param string $methodName The name of the method, without the beginning 'get'.
     * @return array Extracted values.
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
     *  Extracts properties from objects via their get method and flattens the result.
     * 
     * @param array  $listOfObjects List of objects.
     * @param string $methodName The name of the method, without the beginning 'get'.
     * @param bool $keepKeys Keep keys in result. Will overwrite existing values for this key.
     * @return array Extracted flattened values.
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
     * Calls a method in each object and returns the results.
     * 
     * @param array $listOfObjects List of objects.
     * @param string $method name of the method.
     * @return array List of method results.
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
     * Returns a copy of the list with all falsy values (null, 0, '') removed. 
     * @param null|array $arr List with values.
     * @return array List without falsy values
     */
    public static function compact(?array $arr): array
    {
        return array_filter(is_array($arr) ? $arr : []);
    }


    /**
     * Flattens a nested array.
     * 
     * @param array $arr The nested array.
     * @return array The flat array.
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
     * Filters a list with a condition function
     * 
     * @param array $arr The array.
     * @param callable $func The filter closure.
     * @return array The filtered result.
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
     * Iterates each item and maps the new value throught a function.
     * 
     * @param array $arr The array.
     * @param callable $func The transformation function (receives three parametes: 1. value, 2. key, 3. current transformated list).
     * @return array The mapped array.
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
     * Returns the first element from a list.
     * @param array $arr The array.
     * @return mixed First element.
     */
    public static function first(array $arr)
    {
        return reset($arr);
    }


    /**
     * Extracts a the value of a certain key.
     * @param array $arr The array.
     * @param string $key The key.
     * @return array The extracted values.
     */
    public static function pluck(array $arr, string $key): array
    {
        return array_column($arr, $key);
    }


    /**
     * Checks if a value exist in an array.
     * 
     * @param array $arr The array.
     * @param string $needle The value to check.
     * @return bool Check result.
     */
    public static function contains(array $arr, string $needle): bool
    {
        return in_array($needle, is_array($arr) ? $arr : []);
    }

    /**
     * Returns only array entries listed in a whitelist.
     *
     * @param array $array Original array to operate on.
     * @param array $whitelist Keys you want to keep.
     * @return array The whitelisted entries.
     */
    public static function whitelist(array $array, array $whitelist): array
    {
        return array_intersect_key(
            $array,
            array_flip($whitelist)
        );
    }

    /**
     * Returns only nested array entries listed in a whitelist.
     *
     * @param array $array List of nested array items
     * @param array $whitelist keys you want to keep
     * @return array The whitelisted entries.
     */
    public static function whitelistList(array $array, array $whitelist): array
    {
        return static::map($array, function ($a) use ($whitelist) {
            return static::whitelist($a, $whitelist);
        });
    }


    /**
     * Create a indexed list based on key values.
     * 
     * @param array $array The array.
     * @param string $key The index key name.
     * @return array The indexed List.
     */
    public static function indexBy(array $arr, string $key): array
    {
        $r = [];
        foreach ($arr as $a) {
            $r[$a[$key]] = $a;
        }
        return $r;
    }
}
