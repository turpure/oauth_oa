<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-10-26 13:45
 */

namespace backend\modules\v1\utils;


class Helper
{
    /**
     * @brief 多维数组去重
     * @param $arr
     * @param bool $reserveKey
     * @return array
     */
    public static function arrayUnique($arr, $reserveKey = false)
    {
        if (is_array($arr) && !empty($arr))
        {
            foreach ($arr as $key => $value)
            {
                $tmpArr[$key] = serialize($value) . '';
            }
            $tmpArr = array_unique($tmpArr);
            $arr = array();
            foreach ($tmpArr as $key => $value)
            {
                if ($reserveKey)
                {
                    $arr[$key] = unserialize($value);
                }
                else
                {
                    $arr[] = unserialize($value);
                }
            }
        }
        return $arr;
    }

    /**
     * @brief 多维数组排序
     * @param $array
     * @param $keys
     * @param string $sort
     * @return mixed
     */
    public static function arraySort($array, $keys, $sort = 'SORT_DESC') {
        $keysValue = [];
        foreach ($array as $k => $v) {
            $keysValue[$k] = $v[$keys];
        }
        array_multisort($keysValue, $sort, $array);
        return $array;
    }

    /**
     * @brief 数组过滤
     * @param $array
     * @return array
     */
    public static function arrayFilter($array)
    {
        $keysValue = [];
        foreach ($array as $k => $v) {
            if($v !== '') {
                $keysValue[$k] = $v;
            }
        }
        return $keysValue;
    }

    /**
     * @brief 匹配字符串中URL地址并替换成a标签
     * @param $string
     * @return array
     */
    public static function stringFilter($string)
    {
        preg_match_all( '#(http|https|ftp|ftps)://([\w-]+\.)+[\w-]+(/[\w-./?%&=]*)?#i', $string ,$list);
        if($list && isset($list[0])){
            foreach ($list[0] as $k => $v) {
                $keysValue = "<a href='" . $v . "' target='_blank'>" . $v . '</a>';
                $string = str_replace($v, $keysValue, $string);
            }
        }
        return $string;
    }

    /**
     * @brief 生成过滤语句
     * @param $query
     * @param $fields
     * @param $condition
     * @return mixed
     */
    public static function generateFilter($query, $fields,$condition)
    {
        $like = isset($fields['like'])? $fields['like'] : [];
        $equal = isset($fields['equal'])? $fields['equal'] : [];
        foreach ($like as $attr) {
            if (isset($condition[$attr]) && !empty($condition[$attr])) {
                $query->andFilterWhere(['like', $attr, $condition[$attr]]);
            }
        }
        foreach ($equal as $attr) {
            if (isset($condition[$attr])) {
                $query->andFilterWhere(['=', $attr, $condition[$attr]]);
            }
        }
        return $query;
    }

    /**@brief 时间类型过滤器
     * @param $query
     * @param $fields
     * @param $condition
     * @return mixed
     */
    public static function timeFilter($query, $fields, $condition)
    {
        foreach ($fields as $attr) {
            if (isset($condition[$attr]) && !empty($condition[$attr])) {
                $query->andFilterWhere(['between', "date_format($attr,'%Y-%m-%d')", $condition[$attr][0], $condition[$attr][1]]);
            }
        }
        return $query;
    }

}