<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-12-06
 * Time: 16:59
 */

namespace backend\modules\v1\models;


use common\models\User;
use mdm\admin\models\Department;
use Yii;
use yii\helpers\ArrayHelper;

class ApiUser
{

    /**
     * 获取登录用户管辖下的用户姓名
     * @param $username
     * @return array
     * @throws \yii\db\Exception
     */
    public static function getUserList($username)
    {
        //$username='吴凯凯';
        $role = self::getUserRole($username);
        //获取用户部门
        $depart = Department::find()->select('department')
            ->leftJoin('auth_department_child dc', 'auth_department.id=dc.department_id')
            ->leftJoin('user u', 'user_id=u.id')
            ->where(['username' => $username])
            ->asArray()->one();
        if (in_array('超级管理员', $role)) {
            $list = User::find()->select('username')
                ->where(['status' => 10])
                //->andWhere(['NOT IN', 'username', ['admin', '柴盼盼']])
                ->asArray()->all();
            $data = ArrayHelper::getColumn($list, 'username');
        } elseif (in_array('部门经理', $role)) {
            $list = User::find()->select('username')
                ->leftJoin('auth_department_child dc', 'dc.user_id=user.id')
                ->leftJoin('auth_department d', 'd.id=dc.department_id')
                ->leftJoin('auth_department dp', 'dp.id=d.parent')
                ->where(['status' => 10])
                ->andWhere(['OR', ['d.department' => $depart['department']], ['dp.department' => $depart['department']]])
                ->asArray()->all();
            $data = ArrayHelper::getColumn($list, 'username');
        } elseif (in_array('部门主管', $role)) {
            $list = User::find()->select('username')
                ->leftJoin('auth_department_child dc', 'dc.user_id=user.id')
                ->leftJoin('auth_department d', 'd.id=dc.department_id')
                ->leftJoin('auth_department dp', 'dp.id=d.parent')
                ->where(['status' => 10])
                ->andWhere(['d.department' => $depart['department']])
                ->asArray()->all();
            $data = ArrayHelper::getColumn($list, 'username');
        } else {
            $data = [$username];
        }

        return $data;

    }


    /**
     * 根据用户名获取用户角色
     * @param $username
     * @return array
     * @throws \yii\db\Exception
     */
    public static function getUserRole($username)
    {
        //根据角色 过滤
        $sql = "SELECT t2.item_name FROM `user` t1,auth_assignment t2 WHERE  t1.id=t2.user_id AND username='" . $username . "'";
        $roleList = Yii::$app->db->createCommand($sql)->queryAll();
        return ArrayHelper::getColumn($roleList, 'item_name');
    }

}