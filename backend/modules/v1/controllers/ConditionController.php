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

    public function actionDepartment ()
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

    /**
     * 获取仓库列表
     * @return array
     */
    public function actionStore ()
    {
        return ApiCondition::getStore();
    }

    /**
     * 获取用户所管理的销售员列表/开发责任人列表/采购列表/美工列表
     * @return array
     */
    public function actionMember ()
    {
        return ApiCondition::getUsers();
    }

    /**
     * 获取用户所管理的账号列表
     * @return array
     */
    public function actionAccount ()
    {
        return ApiCondition::getUserAccount();
    }


}
