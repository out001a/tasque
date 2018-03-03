<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/3/2
 * Time: 14:42
 */

namespace Tests\Mock;

use Tests\Util\Algo;

class Redis {

    private $_hash;
    private $_zset;

    public function __construct()
    {
    }

    public function connect()
    {
        return true;
    }

    public function pconnect()
    {
        return true;
    }

    public function hGet($key, $field)
    {
        return isset($this->_hash[$key][$field]) ? $this->_hash[$key][$field] : null;
    }

    public function hSet($key, $field, $value)
    {
        $this->_hash[$key][$field] = $value;
        return true;
    }

    public function hDel($key, $field)
    {
        unset($this->_hash[$key][$field]);
        return true;
    }

    public function zCard($key)
    {
        return count($this->_zset[$key]);
    }

    public function zCount($key, $min_score, $max_score)
    {
        if ($min_score > $max_score) {
            list($min_score, $max_score) = [$max_score, $min_score];
        }
        $arr = $this->_zset[$key];
        $min_index = Algo::bsearch($arr, $min_score, -1);
        $max_index = Algo::bsearch($arr, $max_score, 1);
        return $max_index - $min_index + 1;
    }

    public function zAdd($key, $score, $member)
    {
        $this->_zset[$key][$member] = $score;
        asort($this->_zset[$key], SORT_NUMERIC);
        return true;
    }

    public function zRem($key, $member)
    {
        unset($this->_zset[$key][$member]);
        return true;
    }

    public function script($option = 'load', $script)
    {
        return true;
    }

    public function evalSha($sha, $args, $key_num = 1)
    {
        // 仅模拟了zset的pop
        list($key, $start, $stop, $score) = $args;
        $arr = & $this->_zset[$key];
        if ($start < 0) {
            $start = 0;
        }
        if ($stop > count($arr) - 1) {
            $stop = count($arr) - 1;
        }

        $result = [];
        $i = -1;
        foreach ($arr as $m => $s){
            $i++;
            if ($i < $start) {
                continue;
            }
            if ($i > $stop) {
                break;
            }
            if ($s > $score) {
                break;
            } else {
                $result[] = [$m, $s];
                unset($arr[$m]);
            }
        }
        return $result;
    }

}