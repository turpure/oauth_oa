<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-05
 * Time: 10:44
 */

namespace console\models;


use backend\modules\v1\models\ApiReport;
use backend\modules\v1\models\ApiSettings;
use backend\modules\v1\utils\Handler;
use \Yii;
use yii\db\Exception;
use yii\helpers\ArrayHelper;

class ConScheduler{

    /**
     * @param $startDate
     * @param $endDate
     * @param $dateRate
     * Date: 2019-06-13 18:26
     * Author: henry
     * @return bool
     * @throws Exception
     */
    public static function getZzTargetData($startDate, $endDate, $dateRate){
        //获取时间段内销售毛利
        $sql = "SELECT u.username
                FROM `user` u
                 left Join auth_department_child dc ON dc.user_id=u.id
                 left Join auth_department d ON d.id=dc.department_id
                 left Join auth_department p ON p.id=d.parent
                left Join auth_assignment a ON a.user_id=u.id
                WHERE u.`status`=10 AND a.item_name='产品销售' 
                AND (p.department LIKE '郑州分部%' OR d.department LIKE '郑州分部%')";
        $userList = Yii::$app->db->createCommand($sql)->queryAll();
        $userList = ArrayHelper::getColumn($userList,'username');
        $params = [
            'platform' => [],
            'username' => $userList,
            'store' => []
        ];
        $exchangeRate = ApiSettings::getExchangeRate();
        $paramsFilter = Handler::paramsHandler($params);
        $condition = [
            'dateType' => 1,
            'beginDate' => $startDate,
            'endDate' => $endDate,
            'queryType' => $paramsFilter['queryType'],
            'store' => implode(',', $paramsFilter['store']),
            'warehouse' => '',
            'exchangeRate' => $exchangeRate['salerRate']
        ];
        $profit =  ApiReport::getSalesReport($condition);

        //获取需要统计的郑州销售列表
        $saleList = Yii::$app->db->createCommand("SELECT * FROM site_target")->queryAll();
        /*
        //备份上月数据
        $arr = [];
        foreach ($saleList as $v){
            $item1 = [];
            $saleMoneyUs = $profitZn = 0;
            foreach ($profit as $value){
                if($v['username'] == $value['salesman']){
                    $saleMoneyUs += $value['salemoney'];
                    $profitZn += $value['grossprofit'];
                }
            }
            $item1['username'] = $v['username'];
            $item1['saleMoneyUs'] = $saleMoneyUs;
            $item1['profitZn'] = $profitZn;
            $item1['month'] = (int)date('n',strtotime($startDate));
            $item1['updateTime'] = $endDate;
            $arr[] = $item1;
        }
        //批量插入备份表
        $res = Yii::$app->db->createCommand()->batchInsert('site_target_backup_data',['username','saleMoneyUs','profitZn','month','updateTime'],$arr)->execute();
        print_r($res);exit;
*/

        foreach ($saleList as $v){
            $item = $v;
            $amt = 0;
            foreach ($profit as $value){
                if($v['username'] == $value['salesman']){
                    $amt += $value['grossprofit'];
                }
            }
            $item['amt'] = $amt;
            $item['rate'] = round($item['amt']/$item['target'],4);
            $item['dateRate'] = $dateRate;
            $item['updateTime'] = $endDate;
            $res = Yii::$app->db->createCommand()->update('site_target',$item,['id' => $item['id']])->execute();
            if(!$res){
                throw new Exception('update data failed!');
            }
        }
        return true;
    }


}