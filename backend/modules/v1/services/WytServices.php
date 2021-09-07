<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021-09-06
 * Time: 8:45
 * Author: henry
 */

/**
 * @name WytServices.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2021-09-06 8:45
 */


namespace backend\modules\v1\services;

use Yii;

class WytServices
{

    public static function get_signature($token, $params)
    {
        $par = [];
        foreach ($params as $key => $v){
            $par[] = $key.$v;
        }
        sort($par);
        $par_string = implode('', $par);
        $raw_string = $token . $par_string . $token;
        $signature = strtoupper(md5($raw_string));
        return $signature;
    }

    public static function get_request_par($data, $action, $version = '1.0')
    {
        $today = date('Y-m-d H:i:s');
        $params = [
            'app_key' => Yii::$app->params['wyt']['app_key'],
            'platform' => Yii::$app->params['wyt']['platform'],
            'action' => $action,
            'data' => json_encode($data),
            'format' => 'json',
            'timestamp' => $today,
            'sign_method' => 'md5',
            'version' => $version
        ];
        $token = Yii::$app->params['wyt']['token'];
        $client_secret = Yii::$app->params['wyt']['client_secret'];
        $sign = self::get_signature($token, $params);
        $client_sign = self::get_signature($client_secret, $params);
        $params['sign'] = $sign;
        $params['client_sign'] = $client_sign;
        $params['client_id'] = Yii::$app->params['wyt']['client_id'];
        $params['language'] = 'zh_CN';
        $params['data'] = $data;

        return $params;
    }


}
