<?php

namespace backend\modules\v1\controllers;

use backend\models\RestLoginForm;
use backend\models\SignupForm;
use common\models\User;
use Yii;
use yii\web\IdentityInterface;
use backend\modules\v1\utils\Handler;

class UserController extends AdminController
{
    public $modelClass = 'common\models\User';


    /**
     * sing up
     */
    public function actionSignup ()
    {
        $model = new SignupForm();
        $model->setAttributes(Yii::$app->request->post());
        if($model->signup()){
            return [];
        }
        return $model->errors;
    }

    /**
     * login
     */
    public function actionLogin ()
    {
        $model = new RestLoginForm;
        $model->setAttributes(Yii::$app->request->post());
        if ($user = $model->login()) {
            if ($user instanceof IdentityInterface) {
                return ['access_token'=> $user->access_token];
//                return $user;
            } else {
                return $user->errors;
            }
        } else {
            return $model->errors;
        }
    }

    /**
     * 获取用户信息
     */
    public function actionUserProfile ()
    {
        // 到这一步，token都认为是有效的了
        // 下面只需要实现业务逻辑即可，下面仅仅作为案例，比如你可能需要关联其他表获取用户信息等o等
        /* get user by token
        $token = Yii::$app->request->get()['token'];
        $user = User::findIdentityByAccessToken($token);
        */
        // get user by authenticating
        $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'avatar' => $user->avatar,
        ];
    }

    /**
     * 获取用户信息
     */
    public function actionInfo ()
    {

        $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);

        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'isAdmin' => $this->isAdmin()
        ];
    }

    /**
     * @brief 设置头像
     *
     */
    public function  actionAvatar ()
    {
        $post = Yii::$app->request->post();
        $avatar = isset($post['avatar'])?$post['avatar']:'';
        $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
        $userId = $user->id;
        try{
            $image = Handler::baseToImage($avatar, $userId);
            $url = Yii::$app->request->hostInfo;
            $imageUrl = $url.'/'.$image;
            $user->avatar = $imageUrl;
            $user->save();
            return [$imageUrl];
        }
        catch (\Exception $why) {
            return [$why];
        }
    }


    /**
     * @brief whether the user is admin or not
     * @return boolean
     * @throws \Exception
     */
    private function isAdmin()
    {
        $userId = Yii::$app->user->id;
        $db = Yii::$app->db;
        $sql = "select item_name as role from auth_assignment where user_id=$userId";
        $ret = $db->createCommand($sql)->queryOne();
        return $ret['role'] === '超级管理员';
    }


    /*
     * @brief API测试
     */
    public function actionApi()
    {
        $data = Yii::$app->request->post();
        $url = $data['url'];
        $body = json_encode($data['data']);
//      $body['data']= $body['data']?:'{}';
        //先转成json字符串，再替换！！
        $body = str_replace('[]','{}',$body);
        return [Handler::request($url, $body)];
    }

}
