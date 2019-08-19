<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-08-15
 * Time: 16:49
 * Author: henry
 */
/**
 * @name TestController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-08-15 16:49
 */


namespace console\controllers;


use backend\models\OaDataMineDetail;
use yii\console\Controller;
use yii\db\Exception;

class TestController extends Controller
{
    public function actionTest(){
        $sql = "SELECT m.id,m.childId,m.parentId,mid,d.proId,d.goodsCode,d.devStatus
FROM proCenter.oa_dataMineDetail m
LEFT JOIN proCenter.oa_dataMine d ON d.id=m.mid
WHERE childId in(SELECT childId FROM proCenter.oa_dataMineDetail GROUP BY childId HAVING count(childId)>1)
AND m.id NOT IN (SELECT MIN(id) FROM proCenter.oa_dataMineDetail GROUP BY childId HAVING count(childId)>1)
and d.devStatus = '未开发' 
-- AND mid=57289
-- ORDER BY childId
;";
        $arr = \Yii::$app->db->createCommand($sql)->queryAll();

        try{
            foreach ($arr as $v){
                $maxChild = OaDataMineDetail::find()->where(['mid' => $v['mid']])->max('childId');
                $maxArr = explode('_',$maxChild);
                $last = $maxArr[1] + 1;
                $newChild = $maxArr[0].($last > 9 ? '_'.$last : '_0'.$last);

                $res = \Yii::$app->db->createCommand("update proCenter.oa_dataMineDetail set childId='{$newChild}' WHERE id='{$v['id']}'")->execute();
                print_r($res);
                echo "\r\n";
                //exit;
            }
        }catch (Exception $e){
            print_r($e->getMessage());
            exit;
        }






    }
}