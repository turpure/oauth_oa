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
use backend\modules\v1\utils\Helper;
use console\models\ProductEngine;


use backend\models\OaDataMineDetail;
use backend\modules\v1\models\ApiReport;
use console\models\ConScheduler;
use yii\console\Controller;
use yii\db\Exception;
use yii\db\Query;
use Yii;
use yii\helpers\ArrayHelper;

class TestController extends Controller
{

    /**
     * 备份上个月目标完成度数据
     * Date: 2019-12-05 11:52
     * Author: henry
     */
    public function actionSite()
    {
        try {
            //备份上月开发目标完成度 TODO  备份数据的加入
            $seller = Yii::$app->db->createCommand("SELECT distinct username FROM site_target_user WHERE role='开发'")->queryAll();
            $condition = [
                'dateFlag' => 1,
                'beginDate' => '2021-09-01',
                'endDate' => '2021-09-30',
                'seller' => implode(',', ArrayHelper::getColumn($seller, 'username')),
                'flag' => 1
            ];
            $devList = ApiReport::getDevelopReport($condition);
            //print_r($devList);exit;
            foreach ($devList as $value) {
                Yii::$app->db->createCommand()->insert(
                    'site_target_all_backup_data',
                    // ['username','role','profitZn','month','updateTime'],
                    [
                        'username' => $value['salernameZero'],
                        'role' => '开发',
                        'sale_money_us' => $value['salemoneyrmbusZero'] + $value['salemoneyrmbusSix'],
                        'profit_zn' => $value['netprofitZero'] + $value['netprofitSix'],
                        'month' => '2021-09',
                        'updateTime' => date('Y-m-d H:i:s')
                    ]
                )->execute();
            }

            print date('Y-m-d H:i:s') . " INFO:success to get data of target completion!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to get data of target completion cause of $why \n";
        }
    }



    public function actionTest(){
        $seller = Yii::$app->db->createCommand("SELECT isDone,createdTime,updatedTime,batchNumber,picker,scanningMan FROM task_sort ")->queryAll();
        foreach ($seller as $v){
            var_dump($v);
            Yii::$app->py_db->createCommand()->insert('oauth_task_sort',
//                ['isDone','createdTime','updatedTime','batchNumber','picker','scanningMan'],
                $v
            )->execute();
        }

    }



}
