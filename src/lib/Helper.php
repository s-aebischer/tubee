<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

class Helper
{
    /**
     * Get array value via string path.
     *
     * @param iterable $arr
     * @param string   $path
     * @param string   $seperator
     *
     * @return mixed
     */
    public static function getArrayValue(Iterable $array, string $path, string $separator = '.')
    {
        if (isset($array[$path])) {
            return $array[$path];
        }
        $keys = explode($separator, $path);

        foreach ($keys as $key) {
            if (!isset($array[$key])) {
                throw new Exception('array path not found');
            }

            $array = $array[$key];
        }

        return $array;
    }

    /**
     * Convert assoc array to single array.
     *
     * @param iterable $arr
     * @param iterable $narr
     * @param string   $nkey
     *
     * @return array
     */
    public static function associativeArrayToPath(Iterable $arr, Iterable $narr = [], $nkey = ''): array
    {
        if ($nkey !== '') {
            $narr[substr($nkey, 0, -1)] = $arr;
        }

        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $narr = array_merge($narr, self::associativeArrayToPath($value, $narr, $nkey.$key.'.'));
            } else {
                $narr[$nkey.$key] = $value;
            }
        }

        return $narr;
    }

    /**
     * Convert array with keys like a.b to associative array.
     *
     * @param iterable $array
     *
     * @return iterable
     */
    public static function pathArrayToAssociative(Iterable $array): array
    {
        $out = [];
        foreach ($array as $key => $val) {
            $r = &$out;
            foreach (explode('.', $key) as $key) {
                if (!isset($r[$key])) {
                    $r[$key] = [];
                }

                $r = &$r[$key];
            }

            $r = $val;
        }

        return $out;
    }

    /**
     * Compare array.
     *
     * @param array $a1
     * @param array $a2
     *
     * @return bool
     */
    public static function arrayEqual(array $a1, array $a2): bool
    {
        return !array_diff($a1, $a2) && !array_diff($a2, $a1);
    }

    /**
     * Search array element.
     *
     * @param mixed $values
     * @param mixed $key
     * @param array $array
     * @param mixed $value
     */
    public static function searchArray($value, $key, array $array)
    {
        foreach ($array as $k => $val) {
            if ($val[$key] == $value) {
                return $k;
            }
        }

        return null;
    }
}
