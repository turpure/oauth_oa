<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-10-26 13:45
 */

namespace backend\modules\v1\utils;
use console\models\ProductEngine;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yii;


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
        $between = isset($fields['between'])? $fields['between'] : [];
        foreach ($like as $attr) {
            if (isset($condition[$attr]) && !empty($condition[$attr])) {
                $query->andFilterWhere(['like', $attr, $condition[$attr]]);
            }
        }
        foreach ($equal as $attr) {
            if (isset($condition[$attr]) && !empty($condition[$attr])) {
                $query->andFilterWhere(['=', $attr, $condition[$attr]]);
            }
        }

        foreach ($between as $attr) {
            if (isset($condition[$attr]) && !empty($condition[$attr])) {
                list($begin, $end) = $condition[$attr];
                $query->andFilterWhere(['between', $attr, $begin, $end]);
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
    public static function timeFilter($query, $fields, $condition, $type='mysql')
    {
        if ($type === 'mysql') {
            foreach ($fields as $attr) {
                if (isset($condition[$attr]) && !empty($condition[$attr])) {
                    $query->andFilterWhere(['between', "date_format($attr,'%Y-%m-%d')", $condition[$attr][0], $condition[$attr][1]]);
                }
            }
            return $query;
        }

        if ($type === 'mssql') {
            foreach ($fields as $attr) {
                if (isset($condition[$attr]) && !empty($condition[$attr])) {
                    $query->andFilterWhere(['between', "convert(varchar(10),$attr,121)", $condition[$attr][0], $condition[$attr][1]]);
                }
            }
            return $query;
        }


    }


    /**
     * @brief 上传文件
     * @param $file
     * @return bool|string
     */
    public static function file($file)
    {
        $file_name = mt_rand(9000, 10000) . iconv('utf-8', 'GBK',$file['name']);
        $savePath = '/uploads/'  . date('Ymd');
        $model_path = Yii::$app->basePath . '/uploads/';
        $path = Yii::$app->basePath . $savePath . '/';
        if (!file_exists($model_path)) mkdir($model_path, 0777);
        if (!file_exists($path)) mkdir($path, 0777);
        $targetFile = str_replace('//', '/', $path) . $file_name;
        if (!move_uploaded_file($file['tmp_name'], $targetFile)) return false;
        return Yii::$app->basePath. '/' . $savePath . '/' . $file_name;
    }

    public static function readExcel($path)
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(TRUE);
        $spreadsheet = $reader->load($path);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow(); // 取得总行数
        $ret = [];
        for ($row = 2; $row <= $highestRow; $row++) {
            $ele['tradeNid'] = (int)$worksheet->getCell('A'. $row)->getValue();
            $ele['trackNumber'] = (string)$worksheet->getCell('B'. $row)->getValue();
            $ele['expressName'] = (string)$worksheet->getCell('C'. $row)->getValue();
            $ele['isMerged'] = (int)$worksheet->getCell('D'. $row)->getValue();
            $ret[] = $ele;
        }
        return $ret;
    }


    /**
     * PHP发送Json对象数据
     *
     * @param $url 请求url
     * @param $jsonStr 发送的json字符串
     * @return array
     */
    public  static function request($url, $jsonStr)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
//                'Content-Length: ' . strlen($jsonStr)
            )
        );
        //如果用的协议是https则打开下面这个注释
        if(strpos($url,'https:') !== false){
            curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$httpCode, json_decode($response, true)];
    }

    /**
     * @param $url
     * @param $data
     * @param string $method
     * Date: 2020-05-26 14:05
     * Author: henry
     * @return mixed
     */
    public  static function post($url, $jsonData, $header = [], $method = 'POST'){
//        $content = http_build_query($data);
//        $options = array(
//            'http' => array(
//                'method' => $method,
//                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
//                'content' => $content
//            )
//        );
//        $ret = file_get_contents($url, false, stream_context_create($options));
//        return json_decode($ret, true);
        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(array(
                'Content-Type: application/json; charset=utf-8',
            ), $header)
        );
        //如果用的协议是https则打开下面这个注释
        if(strpos($url,'https:') !== false){
            curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
//        return json_decode($response, true);
        return [$httpCode, json_decode($response, true)];
    }


    public  static function curlRequest($url, $data, $header = [], $method = 'POST'){
        $ch = curl_init();
        if($method == 'POST'){
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }else{
            curl_setopt($ch,CURLOPT_URL, $url. (strpos($url, '?') === FALSE ? '?' : ''). http_build_query($data));
        }
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        if($header){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        //如果用的协议是https则打开下面这个注释
        if(strpos($url,'https:') !== false){
            curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }



    public static function pushData(){
        // 推送的url地址，使用自己的服务器地址
        $push_api_url = "tcp://127.0.0.1:5678/";
        $info = ProductEngine::getDailyReportData();
        $client = stream_socket_client($push_api_url, $errno, $errmsg, 1);
        $data = json_encode($info);
        fwrite($client, (string)$data."\n");
        return fread($client, 8192);
    }


    public static function tree($data, $pid = 0){
        $tree = [];
        foreach($data as $k => $v)
        {
            if($v['pid'] == $pid) {
                $item = self::tree($data, $v['id']);
                if($item){
                    $v['children'] = $item;
                }
                $tree[] = $v;
            }
        }
        return $tree;
    }



    /**
     * 获取文件名后缀
     * @param $filename
     * @return string
     */
    public static function get_extension($filename)
    {
        $x = explode('.', $filename);
        return ucfirst(end($x));
    }




}
