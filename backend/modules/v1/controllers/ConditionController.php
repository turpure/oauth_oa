<?php
/**
 * 在毛利润报表中，获取登录用户的所在部门、销售平台、主管的销售员、
 * 出货仓库、管理的账号、业绩归属人
 */
namespace backend\modules\v1\controllers;


use backend\modules\v1\models\ApiCondition;

class ConditionController extends AdminController
{
    public $modelClass = 'backend\models\AuthPositionMenu';



    /**
     * 获取登录用户的所在部门列表
     * @return array
     */

    public function actionIndex ()
    {
        return ApiCondition::getUserPosition();
    }
    /**
     * 获取登录用户的操作平台列表
     * @return array
     */
    public function actionPlat ()
    {
        return ApiCondition::getUserPlat();
    }


}
