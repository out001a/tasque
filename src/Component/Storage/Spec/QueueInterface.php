<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/10
 * Time: 下午12:38
 */

namespace Tasque\Component\Storage\Spec;

interface QueueInterface {

    function __construct($name, $connector);

    function push($score, $member);

    function pop($score, $limit);

    function delByMember($member);

    function delByScore($score_min, $score_max);
}