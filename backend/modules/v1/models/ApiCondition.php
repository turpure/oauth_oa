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
use backend\modules\v1\utils\Helper;

class ApiCondition
{
    /**
     * 获取用户所在一级部门
     * @return array
     */
    public static function getUserDepartment()
    {
        $userInfo = self::getUsers();
        $department = [];
        foreach ($userInfo as $key=>$value) {
            $row = [];
            if(!empty($value['parent_id']) && !empty($value['parent_department'])) {
                $type = explode(',',$value['department_type']);
                foreach ($type as $v){
                    $row['id'] = $value['parent_id'];
                    $row['department'] = $value['parent_department'];
                    $row['order'] = $value['parent_order'];
                    $row['type'] = $v;
                    $department[] = $row;
                }
            }
            else if(!empty($value['department_id']) && !empty($value['department'])) {
                $type = explode(',',$value['department_type']);
                foreach ($type as $v){
                    $row['id'] = $value['department_id'];
                    $row['department'] = $value['department'];
                    $row['order'] = $value['order'];
                    $row['type'] = $v;
                    $department[] = $row;
                }
            }else{
                continue;
            }
        }
        $ret = Helper::arrayUnique($department);
        return Helper::arraySort($ret,'order',SORT_ASC);
    }


    /**
     * 获取用户所在二级部门
     * @return array
     */
    public static function getUserSecDepartment()
    {
        $userInfo = self::getUsers();
        $department = [];
        foreach ($userInfo as $key=>$value) {
            $row = [];
            if($value['parent_department'] !== $value['department'] && ( !empty($value['parent_id']) || !empty($value['parent_department']))) {
                $row['id'] = $value['department_id'];
                $row['department'] = $value['department'];
                $row['parent'] = $value['parent_id'];
                $row['type'] = $value['sec_department_type'];
                $department[] = $row;
            }
        }
        $ret = Helper::arrayUnique($department);
        return Helper::arraySort($ret,'id',SORT_ASC);
    }

    /**
     * 获取用户平台信息
     * @return array
     */
    public static function getUserPlat($type = false)
    {
        $userId = Yii::$app->user->id;
        $role = User::getRole($userId);//登录用户角色
        //获取平台列表
        //if ($role == AuthAssignment::ACCOUNT_ADMIN) {
        if (in_array(AuthAssignment::ACCOUNT_ADMIN,$role) !== false || $type == true) {
            $plat = (new Query())
                ->select('platform as plat')
                ->from('auth_store')
                ->orderBy('platform')
                ->groupBy(['platform'])
                ->where(['used'=>0])
                ->all();
        } else if(in_array(AuthAssignment::ACCOUNT_SERVICE,$role) !== false){
            $plat = (new Query())
                ->select('platform as plat')
                ->from('auth_store')
                ->where(['platform' => 'eBay'])
                ->groupBy(['platform'])
                ->all();
        }else {
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
        //if ($role !== AuthAssignment::ACCOUNT_ADMIN) {
        if (in_array(AuthAssignment::ACCOUNT_ADMIN,$role) !== false) {
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
        if (in_array(AuthAssignment::ACCOUNT_ADMIN,$role) !== false) {
            $account = (new Query())
                ->select('id,store,platform')
                ->from('auth_store')
                ->orderBy('store')
                ->all();
        } else if(in_array(AuthAssignment::ACCOUNT_SERVICE,$role) !== false){
            $account = (new Query())
                ->select('id,store,platform')
                ->from('auth_store')
                ->where(['platform' => 'eBay'])
                ->orderBy('store')
                ->all();
        } else {
            //获取所属部门人员列表
            $users = self::getUsers();
            $users = ArrayHelper::getColumn($users, 'id');
            $account = (new Query())
                ->select('as.id,store,platform')
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
     * 获取用户资源(权限资源控制接口)
     * @return array
     */
    public static function getUsers($flag = false)
    {
        $userId = Yii::$app->user->id;
        $role = User::getRole($userId);//登录用户角色
        $position = AuthPosition::getPosition($userId);//登录用户职位
        $dataScope = User::findOne($userId)['dataScope'];
        if($dataScope == 'all') $flag = true;

        //if ($role === AuthAssignment::ACCOUNT_ADMIN) {
        if (in_array(AuthAssignment::ACCOUNT_ADMIN,$role) !== false || $flag == true) {
            $users = (new Query())->select("u.id,username,p.position,d.department as department,d.id as department_id,	
            IFnull(`pd`.`department`,d.department) AS `parent_department`,IFNULL(`pd`.`id`, d.id) AS `parent_id`,
            IFNULL(pd.`order`,d.`order`) as parent_order,d.order,IFNULL(pd.`type`,d.`type`) as department_type, d.`type` as sec_department_type")
                ->from('`user` as u ')
                ->leftJoin('auth_position_child pc','pc.user_id=u.id')
                ->leftJoin('auth_position p','p.id=pc.position_id')
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
            $users = (new Query())->select("u.id,username,p.position,d.department as department,d.id as department_id,	
            IFnull(`pd`.`department`,d.department) AS `parent_department`,IFNULL(`pd`.`id`, d.id) AS `parent_id`,
            IFNULL(pd.`order`,d.`order`) as parent_order,d.order,IFNULL(pd.`type`,d.`type`) as department_type, d.`type` as sec_department_type")
                ->from('user u')
                ->innerJoin('auth_position_child pc','pc.user_id=u.id')
                ->innerJoin('auth_department_child dc','dc.user_id=u.id')
                ->innerJoin('auth_department d','d.id=dc.department_id')
                ->innerJoin('auth_position p','p.id=pc.position_id')
                ->leftJoin('auth_department pd','pd.id=d.parent')
                ->andWhere(['u.status' => 10])
                ->andWhere(['or',['d.id' => $depart_id],['d.parent' => $depart_id]])->all();
        } elseif (in_array(AuthPosition::JOB_SERVICE, $position) !== false) {
            //登录用户部门
            $users = (new Query())->select("u.id,username,p.position,d.department as department,d.id as department_id,	
            IFnull(`pd`.`department`,d.department) AS `parent_department`,IFNULL(`pd`.`id`, d.id) AS `parent_id`,
            IFNULL(pd.`order`,d.`order`) as parent_order,d.order,IFNULL(pd.`type`,d.`type`) as department_type, d.`type` as sec_department_type")
                ->from('user u')
                ->innerJoin('auth_position_child pc','pc.user_id=u.id')
                ->innerJoin('auth_department_child dc','dc.user_id=u.id')
                ->innerJoin('auth_department d','d.id=dc.department_id')
                ->innerJoin('auth_position p','p.id=pc.position_id')
                ->leftJoin('auth_department pd','pd.id=d.parent')
                ->leftJoin('auth_store_child asc', 'asc.user_id=u.id')
                ->leftJoin('auth_store as', 'as.id=asc.store_id')
                ->andWhere(['as.platform' => 'eBay','u.status' => 10,'p.position' => '销售'])
                ->orWhere(['u.id' => $userId])
                ->groupBy('u.id,username,p.position,d.department,d.id,pd.department,pd.id,IFNULL(pd.`type`,d.`type`)')
                ->all();
        }else {
            $users = (new Query())->select("u.id,username,p.position,d.department as department,d.id as department_id,	
            IFnull(`pd`.`department`,d.department) AS `parent_department`,IFNULL(`pd`.`id`, d.id) AS `parent_id`,
            IFNULL(pd.`order`,d.`order`) as parent_order,d.order,IFNULL(pd.`type`,d.`type`) as department_type, d.`type` as sec_department_type")
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
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
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
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
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
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }

    /**
     * @brief get goods status
     * @return array
     */
    public static function getGoodsCats()
    {
        $sql = 'select NID,CategoryLevel,CategoryName,CategoryParentID,CategoryParentName from B_GoodsCats';
        try {
            return Yii::$app->py_db->createCommand($sql)->queryAll();
        }
        catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }


}
