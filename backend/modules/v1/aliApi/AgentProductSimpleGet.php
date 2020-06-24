<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020-06-24
 * Time: 11:57
 * Author: henry
 */
namespace backend\modules\v1\aliApi;

/**
 * @name AgentProductSimpleGet.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2020-06-24 11:57
 */

use backend\modules\v1\utils\Helper;
use Yii;
use yii\db\Exception;

class AgentProductSimpleGet{

    public $pai_name;
    public $app_key;
    public $app_secret_key;
    public $refresh_token;
    public $token;

    public function __construct ($account)
    {
        $this->pai_name = Yii::$app->params['ali']['api_name'];
        $this->app_key = Yii::$app->params['ali']['app_key'];
        $this->app_secret_key = Yii::$app->params['ali']['app_secret_key'];
        $this->refresh_token = Yii::$app->params['ali']['refresh_token'][$account];
        $this->token = $this->_get_access_token();
    }

    /** 获取token
     * Date: 2020-06-24 14:05
     * Author: henry
     * @return string
     */
    public function _get_access_token(){
        $base_url = 'https://gw.open.1688.com/openapi/param2/1/system.oauth2/getToken/' . $this->app_key;
        $post_data = [
            'grant_type' => 'refresh_token',
            'client_id' => $this->app_key,
            'client_secret' => $this->app_secret_key,
            'refresh_token' => $this->refresh_token,
        ];
        try{
            $ret = Helper::post($base_url, $post_data);
            return $ret['access_token'];
        }catch (Exception $e){
            return '';
        }
    }

    /** 获取签名
     * @param $params
     * Date: 2020-06-24 14:40
     * Author: henry
     * @return string
     */
    public function get_signature($params){
        $url_path = 'param2/1/' . $params['api_type'] . '/' . $params['api_name'] . '/' . $this->app_key;
        $signature_par_dict = $params;
        unset($signature_par_dict['api_type'], $signature_par_dict['api_name']);

        $ordered_par_dict = [];
        foreach ($signature_par_dict as $k => $v){
            $ordered_par_dict[] = $k.$v;
        }
        sort($ordered_par_dict);
        $par_string = implode('', $ordered_par_dict);
        $raw_string = $url_path . $par_string;
        $signature = strtoupper(hash_hmac('sha1', $raw_string, $this->app_secret_key));
        return $signature;
    }

    /** 获取请求链接
     * @param $params
     * Date: 2020-06-24 14:20
     * Author: henry
     * @return string
     */
    public function get_request_url($params){
        $signature = $this->get_signature($params);
        $head = [
            'http://gw.open.1688.com:80/openapi/param2/1/' . $params['api_type'],
            $params['api_name'],
            $this->app_key
        ];
        $url_head = implode('/', $head);
        $para_dict = $params;
        $para_dict['_aop_signature'] = $signature;
        unset($para_dict['api_type'], $para_dict['api_name']);
        $parameter = [];
        foreach ($para_dict as $k => $v){
            $parameter[] = $k . '=' . $v;
        }
        $url_tail = implode("&", $parameter);
        $base_url = $url_head . "?" . $url_tail;
        return $base_url;
    }




}
