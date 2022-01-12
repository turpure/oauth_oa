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

    /**
     * 根据用户名获取用户职位
     * @param $username
     * @return array
     * @throws \yii\db\Exception
     */
    public static function getUserPosition($username)
    {
        //根据角色 过滤
        $sql = "SELECT p.position FROM auth_position_child c
                INNER JOIN `user` u ON u.id=c.user_id
                INNER JOIN auth_position p ON p.id=c.position_id
                WHERE   username='" . $username . "'";
        $roleList = Yii::$app->db->createCommand($sql)->queryAll();
        return ArrayHelper::getColumn($roleList, 'position');
    }



    /**
     * 获取分组/二级部门
     */
    public static function getUserGroup()
    {
        $userId = Yii::$app->user->id;
        $role = User::getRole($userId);
        $sql = "select dm.department 
                from oauthoa.auth_department_child dcd 
                inner JOIN oauthoa.auth_department as dm on dcd.department_id = dm.id 
                where user_id=$userId";
        $db = Yii::$app->db;
        $ret = $db->createCommand($sql)->queryOne();
        $res = $ret['department'];
        if(in_array('部门经理', $role) || in_array('部门主管', $role)){
            $sql2 = "select GROUP_CONCAT(dd.department) AS department 
                    from oauthoa.auth_department_child dcd 
                    inner JOIN oauthoa.auth_department as dm on dcd.department_id = dm.id 
                    inner JOIN oauthoa.auth_department as dd on dm.id = dd.parent 
                    where user_id=$userId GROUP BY dm.department";
            $result = Yii::$app->db->createCommand($sql2)->queryScalar();
//            var_dump($result);exit;
            $res .= ',' . $result;
        }

        return $res;
    }

    /**
     * 获取分组/二级部门
     */
    public static function getUserGroupByUserName($userName)
    {
        $sql = "select dm.department 
                from oauthoa.auth_department_child dcd 
                inner JOIN oauthoa.auth_department as dm on dcd.department_id = dm.id  
                INNER JOIN oauthoa.`user` as ur on ur.id = dcd.user_id 
                where username= '$userName' ";
        $db = Yii::$app->db;
        $ret = $db->createCommand($sql)->queryOne();
        return $ret['department'];
    }



}
