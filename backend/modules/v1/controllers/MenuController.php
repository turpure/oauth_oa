<?php

namespace backend\modules\v1\controllers;

use backend\models\AuthPositionMenu;


class MenuController extends AdminController
{
    public $modelClass = 'backend\models\AuthPositionMenu';



    /**
     * 获取登录用户的访问菜单
     * @return mixed
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionIndex ()
    {
        return AuthPositionMenu::getAuthMenuList();
    }


}
