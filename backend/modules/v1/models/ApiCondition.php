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
use common\models\User;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class ApiCondition
{
    /**
     * 获取用户所在一级部门
     * @return array
     */
    public static function getUserDepartment()
    {
        $userId = Yii::$app->user->id;
        $role = User::getRole($userId);
        $depart_id = AuthDepartmentChild::findOne(['user_id' => $userId])['department_id'];//登录用户部门
        $parentId = AuthDepartment::findOne(['id' => $depart_id])['parent'];
        $isParent = (int)$parentId > 0? false:true;
        if ($role !== AuthAssignment::ACCOUNT_ADMIN) {
            if($isParent) {
                $depart = AuthDepartmentChild::find()
                    ->select('auth_department.department,auth_department_child.department_id')
                    ->JoinWith('department')
                    ->where(['user_id' => $userId])
                    ->andWhere(
                        ['auth_department.id' => $depart_id]
                    )
                    ->asArray()->all();
                $_arr = [];
                if ($depart) {
                    foreach ($depart as $k => $v) {
                        $_arr[$k]['id'] = $v['department']['id'];
                        $_arr[$k]['department'] = $v['department']['department'];
                    }
                }
                $department = $_arr;
            }
            else {
                $department = AuthDepartment::find()
                    ->select('department,id')
                    ->andWhere(
                        ['id' => $parentId]
                    )
                    ->asArray()->all();
            }

        } else {
            $department = AuthDepartment::find()
                ->select('id,department')
                ->where(['<>', 'department', '采购部'])
                ->andWhere(['parent' => 0])
                ->asArray()->all();
        }
        return $department;
    }


    /**
     * 获取用户所在二级部门
     * @return array
     */
    public static function getUserSecDepartment()
    {
        $userId = Yii::$app->user->id;
        $role = User::getRole($userId);
        $depart_id = AuthDepartmentChild::findOne(['user_id' => $userId])['department_id'];//登录用户部门
        $parentId = AuthDepartment::findOne(['id' => $depart_id])['parent'];
        $isParent = (int)$parentId > 0? false:true;
        if ($role !== AuthAssignment::ACCOUNT_ADMIN) {
            if($isParent) {
                $depart= AuthDepartmentChild::find()
                    ->select('department,auth_department_child.department_id')
                    ->JoinWith('department')
                    ->where(['user_id' => $userId])
                    ->andWhere(
                        ['auth_department.parent' => $depart_id])
                    ->asArray()->all();
                $_arr = [];
                if ($depart) {
                    foreach ($depart as $k => $v) {
                        $_arr[$k]['id'] = $v['department']['id'];
                        $_arr[$k]['department'] = $v['department']['department'];
                        $_arr[$k]['parent'] = $v['department']['parent'];
                    }
                }
                $department = $_arr;
            }
            else {
                $department = AuthDepartment::find()
                    ->select('department,id,parent')
                    ->andWhere(
                        ['id' => $depart_id]
                    )
                    ->asArray()->all();
            }

        } else {
            $department = AuthDepartment::find()
                ->select('id,department,parent')
                ->where(['<>', 'department', '采购部'])
                ->andWhere(['<>','parent' , 0])
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
                ->groupBy(['platform'])
                ->where(['used'=>0])
                ->all();
        } else {
            //获取所属部门人员列表
            $users = self::getUsers();
            $users = ArrayHelper::getColumn($users, 'id');
            $plat = (new Query())
                ->select('platform as plat')
                ->from('auth_store_child asc')
                ->leftJoin('auth_store as ast', 'ast.id=asc.store_id')
                ->where(['in', 'user_id', $users])
                ->andWhere(['used'=>0])
                ->orderBy('platform')
                ->groupBy(['ast.platform'])
                ->all();
        }
        return $plat;
    }


    /**
     * 获取用户管理的销售员列表
     * @return array  TODO
     */
    public static function getUserSales()
    {
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
     * 获取用户管理的账号列表
     * @return array
     */
    public static function getUserAccount(){
        $userId = Yii::$app->user->id;
        $role = User::getRole($userId);//登录用户角色
        //获取平台列表
        if ($role == AuthAssignment::ACCOUNT_ADMIN) {
            $account = (new Query())
                ->select('id,store')
                ->from('auth_store')
                ->orderBy('store')
                ->all();
        } else {
            //获取所属部门人员列表
            $users = self::getUsers();
            $users = ArrayHelper::getColumn($users, 'id');
            $account = (new Query())
                ->select('as.id,store')
                ->from('auth_store_child asc')
                ->leftJoin('auth_store as', 'as.id=asc.store_id')
                ->where(['in', 'user_id', $users])
                ->orderBy('store')
                ->distinct()
                ->all();
        }
        return $account;
    }


    /**
     * 获取用户资源
     * @return array
     */
    public static function getUsers()
    {
        $userId = Yii::$app->user->id;
        $role = User::getRole($userId);//登录用户角色
        $position = AuthPosition::getPosition($userId);//登录用户职位
        if ($role === AuthAssignment::ACCOUNT_ADMIN) {
            $users = (new Query())->select("u.id,username,p.position,d.department,pd.department as parent_depart")
                ->from('`user` as u ')
                ->innerJoin('auth_position_child pc','pc.user_id=u.id')
                ->innerJoin('auth_position p','p.id=pc.position_id')
                ->leftJoin('auth_department_child dc','dc.user_id=u.id')
                ->leftJoin('auth_department d','d.id=dc.department_id')
                ->leftJoin('auth_department pd','pd.id=d.parent')
                ->andWhere(['u.status' => 10])
                ->distinct()
                ->all();
        } elseif (in_array(AuthPosition::JOB_MANAGER, $position) ||
            in_array(AuthPosition::JOB_CHARGE, $position)
        ) {
            //登录用户部门
            $depart_id = AuthDepartmentChild::findOne(['user_id' => $userId])['department_id'];
            $users = (new Query())->select('u.id,username,p.position,pd.id as department_id,pd.parent,d.department,pd.department as parent_depart')
                ->from('user u')
                ->innerJoin('auth_position_child pc','pc.user_id=u.id')
                ->innerJoin('auth_department_child dc','dc.user_id=u.id')
                ->innerJoin('auth_department d','d.id=dc.department_id')
                ->innerJoin('auth_position p','p.id=pc.position_id')
                ->leftJoin('auth_department pd','pd.id=d.parent')
                ->andWhere(['u.status' => 10])
                ->andWhere(['or',['d.id' => $depart_id],['d.parent' => $depart_id]])->all();
        } else {
            $users = (new Query())->select('u.id,username,p.position,pd.id as department_id,pd.parent,d.department,pd.department as parent_depart')
                ->from('user u')
                ->innerJoin('auth_position_child pc','pc.user_id=u.id')
                ->innerJoin('auth_position p','p.id=pc.position_id')
                ->innerJoin('auth_department_child dc','dc.user_id=u.id')
                ->innerJoin('auth_department d','d.id=dc.department_id')
                ->leftJoin('auth_department pd','pd.id=d.parent')
                ->andWhere(['u.status' => 10])
                ->andWhere(['u.id' => $userId])
                ->all();
        }
        return $users;
    }


    /**
     * @brief 获取仓库列表
     * @return array
     */
    public static function getStore()
    {
        $sql = 'select StoreName from  B_store';
        $ret = Yii::$app->py_db->createCommand($sql)->queryAll();
        return ArrayHelper::getColumn($ret,'StoreName');
    }

    /**
     * @brief get brand country
     * @return array
     */
    public static function getBrandCountry()
    {
        $sql = 'select distinct country from Y_Brand';
        try {
            $ret = Yii::$app->py_db->createCommand($sql)->queryAll();
            return ArrayHelper::getColumn($ret,'country');
        }
        catch (\Exception $why) {
            return [$why];
        }
    }

    /**
     * @brief get brand category
     * @return array
     */
    public static function getBrandCategory()
    {
        $sql = 'select distinct category from Y_Brand';
        try {
            $ret = Yii::$app->py_db->createCommand($sql)->queryAll();
            return ArrayHelper::getColumn($ret,'category');
        }
        catch (\Exception $why) {
            return [$why];
        }
    }

    /**
     * @brief get goods status
     * @return array
     */
    public static function getGoodsStatus()
    {
        $sql = 'select dictionaryName as goodsStatus from B_Dictionary  where CategoryID=15';
        try {
            $ret = Yii::$app->py_db->createCommand($sql)->queryAll();
            return ArrayHelper::getColumn($ret,'goodsStatus');
        }
        catch (\Exception $why) {
            return [$why];
        }
    }

    /**
     * @brief get goods status
     * @return array
     */
    public static function getGoodsCats()
    {
        $sql = 'select CategoryLevel,CategoryName,CategoryParentName from B_GoodsCats';
        try {
            return Yii::$app->py_db->createCommand($sql)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }
    }

}