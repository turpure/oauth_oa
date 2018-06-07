<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-06-06
 * Time: 11:13
 */

namespace backend\modules\v1\models;

use backend\models\AuthAssignment;
use backend\models\AuthDepartment;
use backend\models\AuthDepartmentChild;
use backend\models\AuthPosition;
use backend\models\AuthPositionChild;
use common\models\User;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class ApiCondition
{
    /**
     * 获取用户部门信息
     * @return array
     */
    public static function getUserPosition()
    {
        $userId = Yii::$app->user->id;
        $role = User::getRole($userId);
        if ($role !== AuthAssignment::ACCOUNT_ADMIN) {
            $depart = AuthDepartmentChild::find()
                ->select('auth_department.department,auth_department_child.department_id')
                ->JoinWith('department')
                ->where(['user_id' => $userId])
                ->andWhere(['auth_department.parent' => 0])
                ->asArray()->all();
            $_arr = [];
            if ($depart) {
                foreach ($depart as $k => $v) {
                    $_arr[$k]['department'] = $v['department']['department'];
                }
            }
            $department = $_arr;
        } else {
            $department = AuthDepartment::find()
                ->select('department')
                ->andWhere(['parent' => 0])
                ->asArray()->all();
        }
        return $department;
    }

    /**
     * 获取用户平台信息
     * @return array
     */
    public static function getUserPlat()
    {
        $userId = Yii::$app->user->id;
        $role = User::getRole($userId);//登录用户角色
        //获取平台列表
        if ($role == AuthAssignment::ACCOUNT_ADMIN) {
            $plat = (new Query())
                ->select('platform as plat')
                ->from('auth_store')
                ->orderBy('platform')
                ->groupBy('platform')
                ->all();
        } else {
            //获取所属部门人员列表
            $users = self::getUsers();
            $users = ArrayHelper::getColumn($users, 'id');
            $plat = (new Query())
                ->select('platform as plat')
                ->from('auth_store_child asc')
                ->leftJoin('auth_store as', 'as.id=asc.store_id')
                ->where(['in', 'user_id', $users])
                ->orderBy('platform')
                ->groupBy('platform')
                ->all();
        }
        return $plat;
    }

    /**
     * 获取用户管理的销售员列表
     * @return array
     */
    public static function getUserSales()
    {//TODO
        $userId = Yii::$app->user->id;
        $role = User::getRole($userId);
        //获取部门列表
        if ($role !== AuthAssignment::ACCOUNT_ADMIN) {
            $sales = (new Query())
                ->select('auth_department.department,auth_department_child.department_id')
                ->from('auth_')
                ->where(['user_id' => $userId]);
        } else {
            $sales = AuthDepartment::find()
                ->select('department')
                ->asArray()->all();
        }
        return $sales;
    }

    /**
     * 获取登录用户管辖的用户列表
     * @return array
     */
    public static function getUsers()
    {
        $userId = Yii::$app->user->id;
        $role = User::getRole($userId);//登录用户角色
        $position = AuthPosition::getPosition($userId);//登录用户职位
        $depart_id = AuthDepartmentChild::findOne(['user_id' => $userId])['department_id'];//登录用户部门
        if ($role == AuthAssignment::ACCOUNT_ADMIN) {
            $users = User::find()->select('id,username')->asArray()->all();
        } elseif (in_array(AuthPosition::JOB_MANAGER, $position) ||
            in_array(AuthPosition::JOB_CHARGE, $position)
        ) {
            $users = (new Query())->select('u.id,username')
                ->from('user u')
                ->leftJoin('auth_department_child dc','dc.user_id=u.id')
                ->leftJoin('auth_department d','d.id=dc.department_id')
                ->where(['or',['d.id' => $depart_id],['parent' => $depart_id]])
                ->all();
        /*} elseif (in_array(AuthPosition::JOB_CHARGE, $position)) {
            $users = (new Query())->select('u.id,username')
                ->from('user u')
                ->leftJoin('auth_department_child dc','dc.user_id=u.id')
                ->leftJoin('auth_department d','d.id=dc.department_id')
                ->where(['or',['d.id' => $depart_id],['parent' => $depart_id]])
                ->all();*/
        } else {
            $users = User::find()->select('id,username')
                ->where(['id' => $userId])->asArray()->all();
        }
        return $users;
    }


}