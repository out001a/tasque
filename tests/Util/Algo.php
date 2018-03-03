<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/3/3
 * Time: 9:53
 */

namespace Tests\Util;

class Algo {

    public static function bsearch($arr, $target, $direction = 0, $l = 0, $h = null)
    {
        if (is_null($h)) {
            $h = count($arr) - 1;
        }
        if ($arr[$l] > $target || $arr[$h] < $target) {
            return false;
        }
        if ($l > $h) {
            return false;
        }

        $m = $l + intval(($h - $l) / 2);
        $v = $arr[$m];
        if ($v < $target) {
            return self::bsearch($arr, $target, $direction, $m + 1, $h);
        } elseif ($v > $target) {
            return self::bsearch($arr, $target, $direction, $l, $m - 1);
        } else {
            switch ($direction) {
                case 0:
                    return $m;
                    break;
                case -1:    // left most
                    if ($m == $l || $v > $arr[$m - 1]) {
                        return $m;
                    } else {
                        return self::bsearch($arr, $target, $direction, $l, $m - 1);
                    }
                    break;
                case 1:     // right most
                    if ($m == $h || $v < $arr[$m + 1]) {
                        return $m;
                    } else {
                        return self::bsearch($arr, $target, $direction, $m + 1, $h);
                    }
                    break;
            }
        }
    }
}