<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021-03-11
 * Time: 17:48
 * Author: henry
 */
/**
 * @name ApiTask.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2021-03-11 17:48
 */


namespace backend\modules\v1\models;


use backend\modules\v1\utils\Handler;
use Yii;
class ApiTask
{
    /**
     * getProfitData
     * @param int $type
     * Date: 2021-03-11 17:51
     * Author: henry
     * @return mixed
     * @throws \yii\db\Exception
     */
    public static function getTurnoverData(){
        $user = Yii::$app->user->identity->username;
        $userList = ApiUser::getUserList($user);
        $userStr = implode(',', $userList);
        $sql = "CALL oauth_taskStockTurnover ('{$userStr}')";
        return Yii::$app->db->createCommand($sql)->queryAll();
    }

    /**
     * getProfitData
     * @param int $type
     * Date: 2021-03-11 17:51
     * Author: henry
     * @return mixed
     * @throws \yii\db\Exception
     */
    public static function getProfitData($type = 0){
        $user = Yii::$app->user->identity->username;
        $userList = ApiUser::getUserList($user);
        $userStr = implode(',', $userList);
        $sql = "CALL oauth_taskOfGoodsProfitResult ($type,'{$userStr}')";
        return Yii::$app->db->createCommand($sql)->queryAll();
    }

}
