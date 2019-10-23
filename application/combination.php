<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/5/20
 * Time: 10:58
 */
//阶乘相关
// 阶乘
if(!function_exists('factorial')) {
    function factorial($n) {
        return array_product(range(1, $n));
    }
}

// 排列数
if(!function_exists('arrangement_count')) {
    function arrangement_count($n, $m) {
        return factorial($n) / factorial($n - $m);
    }
}
// 组合数
if(!function_exists('combination_count')) {
    function combination_count($n, $m)
    {
        return arrangement_count($n, $m) / factorial($m);
    }
}
/**
 * 排列
 * @param array $a
 * 要排列的数构成的数组
 * @param int $m
 * @return array
 * 返回所有的排列情况
 */
if(!function_exists('arrangement')) {
    function arrangement($a, $m) {
        $r = array();

        $n = count($a);
        if ($m <= 0 || $m > $n) {
            return $r;
        }

        for ($i = 0; $i < $n; $i++) {
            $b = $a;
            $t = array_splice($b, $i, 1);
            if ($m == 1) {
                $r[] = $t;
            } else {
                $c = arrangement($b, $m - 1);
                foreach ($c as $v) {
                    $r[] = array_merge($t, $v);
                }
            }
        }
        return $r;
    }
}

/**
 * 组合
 * @param array $a
 * 要组合的数构成的数组
 * @param int $m
 * @return array
 * 返回所有的组合情况
 */
if(!function_exists('combination')) {
    function combination($a, $m) {
        $r = array();

        $n = count($a);
        if ($m <= 0 || $m > $n) {
            return $r;
        }

        for ($i = 0; $i < $n; $i++) {
            $t = array($a[$i]);
            if ($m == 1) {
                $r[] = $t;
            } else {
                $b = array_slice($a, $i + 1);
                $c = combination($b, $m - 1);
                foreach ($c as $v) {
                    $r[] = array_merge($t, $v);
                }
            }
        }

        return $r;
    }
}