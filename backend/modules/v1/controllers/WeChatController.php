<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-12-23
 * Time: 16:04
 * Author: henry
 */
/**
 * @name WeChatController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-12-23 16:04
 */


namespace backend\modules\v1\controllers;

use Yii;
class WeChatController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiProductsEngine';

    public $serializer = [
        'class' => 'backend\modules\v1\utils\PowerfulSerializer',
        'collectionEnvelope' => 'items',
    ];
    public $callback_url;

    /**
     * login
     */
    public function actionLogin()
    {
        $app_id = Yii::$app->params['appid'];
        $app_secret = Yii::$app->params['secret'];
        $code = Yii::$app->request->post('code');

        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$app_id.'&secret='.$app_secret.'&js_code='.$code.'&grant_type=authorization_code';
        $res = file_get_contents($url);
        $data = json_decode($res);
        if(array_key_exists('errcode', $data) && $data->errcode){
            return ['code' => $data->errcode, 'message' => $data->errmsg];
        }else{
            return ['code' => 200, 'message' => 'successful', 'data' => $data];
        }
    }



    /**
     * 登录返回函数
     * @return array|\yii\web\Response
     */
    public function actionCallback()
    {
        $sso_token = Yii::$app->request->get('sso_token');
        if (!$sso_token) return ['code' => self::$CODE_ERR, 'msg' => 'Token不能为空'];

        $domain = Yii::$app->params['ssoLoginServer'];;
        $request_url = $domain . '/verify_login?access_token=' . $sso_token;
        $result = json_decode(file_get_contents($request_url), true);

        $callback_url = Yii::$app->session->get('callback_url');

        //保存到cookie
        $value = '';
        $expire = time() + 3600 * 24 * 30;
        $path = Yii::$app->params['img_host'];
        $host = str_replace('http://','',$path);
        if (isset($result['code']) && $result['code'] == 200) {
            $value = Des::encrypt($result['vmb_response']['accountid']);
        } else {
            return $this->redirect($callback_url);
        }

        setrawcookie("account", $value, $expire, '/', $host);
        setrawcookie("expire", $expire, $expire, '/', $host);

        //提取登陆后要跳转的路径
        $callback_url = $callback_url ? $callback_url : $this->callback_url;
        if (!$callback_url) {
            $callback_url = Yii::$app->params['img_host'] . ($this->isMobile() ? '/wap' : '/web');
        }
        return $this->redirect($callback_url);
    }

    /**退出
     */
    public function actionLogout()
    {
        //保存用户当前页到session
        $callback_url = Yii::$app->request->get('callback_url');
        if (!$callback_url) return ['code' => self::$CODE_ERR, 'msg' => '回调地址不能为空'];
        Yii::$app->session->set('callback_url2', $callback_url);

        //清除cookie
        $path = Yii::$app->params['img_host'];
        $host = str_replace('http://', '', $path);

        setrawcookie("account", '', time() - 3600, '/', $host);
        setrawcookie("expire", '', time() - 3600, '/', $host);

        $domain = Yii::$app->params['ssoLoginServer'];#"http://sso2.vmobel.cn";
        $collegecode = Yii::$app->params['sso_collegecode'];
        $app_id = Yii::$app->params['sso_app_id'];
        $app_secret = Yii::$app->params['sso_app_secret'];
        $params = [
            'nonce' => strtolower(Yii::$app->security->generateRandomString(8)),
            'timestamp' => time(),
            'sign_method' => 'md5',
            'format' => 'json',
            'v' => '1.0',
            'partner_id' => 'vmb-sdk-python',
            'method' => 'token',
            'terminal' => $this->isMobile() ? 'wechat' : 'pc',
            'app_id' => $app_id,
            'collegecode' => $collegecode,
            'nexturl' => Yii::$app->params['api_url'] . '/user/login/callback2?t=1',
        ];
        ksort($params);
        $merge_string = 'secret=' . $app_secret;
        foreach ($params as $k => $v) {
            $merge_string .= '&' . strtolower($k) . '=' . strtolower($v);
        }
        $merge_string .= '&secret=' . $app_secret;
        $sign = strtoupper(md5($merge_string));
        $request_url = $domain . ($this->isMobile() ? '/wechat_unbind_by_accountid' : '/logout') . '?sign=' . $sign;
        foreach ($params as $k => $v) {
            if ($k == 'callback_url') {
                $v = urlencode($v);
            }
            $request_url .= '&' . $k . '=' . $v;
        }

        $res = file_get_contents($request_url);
        return ['code' => self::$CODE_SUC, 'msg' => 'successful', 'data' => $request_url];
    }

    /**
     * 退出返回函数
     * @return array|\yii\web\Response
     */
    public function actionCallback2()
    {
        Yii::$app->session->remove('account_id');
        Yii::$app->session->remove('callback_url');
        Yii::$app->session->destroy();

        $callback = Yii::$app->session->get('callback_url2');
        if (!$callback) {
            $callback = Yii::$app->params['img_host'] . ($this->isMobile() ? '/wap' : '/web');
        }
        return $this->redirect($callback);
    }


    public function curl_get($durl)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $durl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }


    public function actionWechatlogout()
    {
        //销毁session
        Yii::$app->session->remove('account_id');
        Yii::$app->session->remove('callback_url');
        Yii::$app->session->destroy();

        //清除cookie
        $path = Yii::$app->params['img_host'];
        $host = str_replace('http://', '', $path);
        setrawcookie("account", '', time() - 3600, '/', $host);
        setrawcookie("expire", '', time() - 3600, '/', $host);


        $domain = Yii::$app->params['ssoLoginServer'];#"http://sso2.vmobel.cn";
        $collegecode = Yii::$app->params['sso_collegecode'];
        $app_id = Yii::$app->params['sso_app_id'];
        $app_secret = Yii::$app->params['sso_app_secret'];
        $params = [
            'nonce' => strtolower(Yii::$app->security->generateRandomString(8)),
            'timestamp' => time(),
            'sign_method' => 'md5',
            'format' => 'json',
            'v' => '1.0',
            'partner_id' => 'vmb-sdk-python',
            'method' => 'token',
            'terminal' => $this->isMobile() ? 'wechat' : 'pc',
            'app_id' => $app_id,
            'collegecode' => $collegecode,
            'nexturl' => $path . '/wap?t=1',
        ];
        ksort($params);
        $merge_string = 'secret=' . $app_secret;
        foreach ($params as $k => $v) {
            $merge_string .= '&' . strtolower($k) . '=' . strtolower($v);
        }
        $merge_string .= '&secret=' . $app_secret;
        $sign = strtoupper(md5($merge_string));
        $request_url = $domain . ($this->isMobile() ? '/wechat_unbind_by_accountid' : '/logout') . '?sign=' . $sign;
        foreach ($params as $k => $v) {
            if ($k == 'callback_url') {
                $v = urlencode($v);
            }
            $request_url .= '&' . $k . '=' . $v;
        }

        //$res = file_get_contents($request_url);
        //echo $request_url;exit;
//        return ['code' => self::$CODE_SUC, 'msg' => 'successful', 'data' => $request_url];
        $this->redirect($request_url);
    }







}