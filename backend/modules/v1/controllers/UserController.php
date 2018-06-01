<?php

namespace backend\modules\v1\controllers;

use backend\models\RestLoginForm;
use backend\models\SignupForm;
use Yii;
use yii\web\IdentityInterface;
use backend\modules\v1\controllers\AdminController;


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
        ];
    }


}
