<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-05
 * Time: 10:44
 */

namespace console\models;


use backend\models\EbayAllotRule;
use backend\modules\v1\models\ApiReport;
use backend\modules\v1\models\ApiSettings;
use backend\modules\v1\utils\Handler;
use \Yii;
use yii\db\Exception;
use yii\helpers\ArrayHelper;

class ConScheduler
{

    /**
     * @param $startDate
     * @param $endDate
     * @param $dateRate
     * Date: 2019-06-13 18:26
     * Author: henry
     * @return bool
     * @throws Exception
     */
    public static function getZzTargetData($startDate, $endDate, $dateRate)
    {
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
        $userList = ArrayHelper::getColumn($userList, 'username');
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
        $profit = ApiReport::getSalesReport($condition);

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
        //print_r($arr);exit;
        //批量插入备份表
        $res = Yii::$app->db->createCommand()->batchInsert('site_target_backup_data',['username','saleMoneyUs','profitZn','month','updateTime'],$arr)->execute();
        print_r($res);exit;
*/
        foreach ($saleList as $v) {
            $item = $v;
            $amt = 0;
            foreach ($profit as $value) {
                if ($v['username'] == $value['salesman']) {
                    $amt += $value['grossprofit'];
                }
            }
            $item['amt'] = $amt;
            $item['rate'] = round($item['amt'] / $item['target'], 4);
            $item['dateRate'] = $dateRate;
            $item['updateTime'] = $endDate;
            $res = Yii::$app->db->createCommand()->update('site_target', $item, ['id' => $item['id']])->execute();
            if ($res === false) {
                throw new Exception('update data failed!');
            }
        }
        return true;
    }


    /**
     * 获取推荐人列表
     * @return mixed
     */
    private static function getRecommendToPersons($today)
    {
        $mongodb = Yii::$app->mongodb;
        $table = 'ebay_recommended_product';
        $col = $mongodb->getCollection($table);
        $products = $col->find(['dispatchDate' => ['$regex' => $today]]);
        return $products;
    }

    /**
     * 为每日推荐列表设置推荐人
     * @param $products
     * @param $developers
     * @param $productType
     * @param $itemId
     */
    private static function setRecommendToPersons($products,$developers, $productType, $itemId)
    {
        $mongodb = Yii::$app->mongodb;
        $table = $productType === 'new' ? 'ebay_new_product' : 'ebay_hot_product';
        $col = $mongodb->getCollection($table);
        $currentPersons = static::insertOrUpdateOrDeleteRecommendToPersons($products,$developers);
        $col->update(['itemId' => $itemId], ['recommendToPersons' => $currentPersons]);

    }

    /**
     * 更新或新增推荐人
     * @param $product
     * @param $persons
     * @return array
     */
    private static function insertOrUpdateOrDeleteRecommendToPersons($product, $persons)
    {
        $refuse = isset($product['refuse']) ? $product['refuse'] : [];
        $accept = isset($product['accept']) ? $product['accept'] : [];
        $person = ['name' =>'', 'status' => '', 'reason' => ''];
        $ret = [];
        foreach ($persons as $pn) {
            if(in_array($pn, $accept, false)) {
                $row = $person;
                $row['name'] = $pn;
                $row['status'] = 'accept';
                $ret[] = $row;
            }
            elseif(array_key_exists($pn, $refuse)) {
                $row = $person;
                $row['name'] = $pn;
                $row['status'] = 'refuse';
                $row['reason'] = $refuse[$pn];
                $ret[] = $row;
            }
            else {
                $row = $person;
                $row['name'] = $pn;
                $ret[] = $row;
            }
        }

        return $ret;

    }


    /**
     * 获取并更新每日推荐的推荐人
     */
    public static function getAndSetRecommendToPersons($today = '')
    {
        if(!$today) $today = date('Y-m-d'); //设置默认今天
        // 清空今日推荐人
        static::clearTodayPersons($today);
        $products = static::getRecommendToPersons($today);
       foreach ($products as $recommendProduct) {
           $productType = $recommendProduct['productType'];
           $developers = $recommendProduct['receiver'];
           $itemId = $recommendProduct['itemId'];
           static::setRecommendToPersons($recommendProduct,$developers, $productType, $itemId);
       }
    }

    /**
     * 清空今日推荐人
     */
    private static function clearTodayPersons($today)
    {
        $tables = ['ebay_new_product', 'ebay_hot_product'];
        $mongo = Yii::$app->mongodb;
        foreach ($tables as $ts) {
            $col = $mongo->getCollection($ts);
            $products = $col->find(['recommendDate' => ['$regex' => $today]]);
            foreach ($products as $row) {
                $col->update(['_id' => $row['_id']],['recommendToPersons' => []]);
            }
        }
    }

    //=================================================================

    /**
     * Date: 2020-03-27 10:54
     * Author: henry
     */
    public static function getWarehouseIntegralData(){
        $month = date('Y-m', strtotime('-1 days'));
        $beginDate = $month . '-01';
        $endDate = date('Y-m-d', strtotime('-1 days'));//昨天时间
        //$beginDate = '2020-05-01';
        //$endDate = '2020-05-31';
        //var_dump($num);exit;

        $userQuery = Yii::$app->db->createCommand('SELECT * FROM warehouse_user_info')->queryAll();
        $user = ArrayHelper::getColumn($userQuery,'name');
        $userPara = implode(',', $user);
        $dataQuery = Yii::$app->py_db->createCommand("EXEC oauth_siteWarehouseIntegral '{$beginDate}','{$endDate}','$userPara'")->queryAll();

        //将数据保存到临时表中
        Yii::$app->db->createCommand('TRUNCATE TABLE  warehouse_integral_data_tmp')->execute();
        Yii::$app->db->createCommand()->batchInsert(
            'warehouse_integral_data_tmp',
            ['username','month','caiGouRuKuBaoGuo','ruKuBaoGuo','ruKuNum','pdaSkuNum','danPinBaoGuo','heDanBaoGuo','zongBaoGuo',
                'jianHuoShuLiang','janHuoSkuZhongShu','danPinBaoGuoDaBao','heDanBaoGuoDaBao','dateRate'],
            $dataQuery
        )->execute();

        //处理临时数据
        Yii::$app->db->createCommand("CALL warehouse_integral_data_parser('{$endDate}');")->execute();

        //计算排行榜
        Yii::$app->db->createCommand('CALL warehouse_intrgral_ranking();')->execute();

    }

}
