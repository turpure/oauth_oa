<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-24
 * Time: 13:35
 */

namespace backend\modules\v1\models;

use Yii;
use yii\data\ArrayDataProvider;
use yii\db\Exception;
use yii\helpers\ArrayHelper;

class ApiPerform
{

    /**
     * @param $condition
     * @return array|string
     */
    public static function getNewProductDevelopmentPerformance($condition)
    {
        $params['DateFlag'] = 0;//0表示交易时间
        $params['BeginDate'] = $condition['beginDate'];
        $params['EndDate'] = $condition['endDate'];
        $params['CreateBeginDate'] = $condition['createBeginDate'];
        $params['CreateEndDate'] = $condition['createEndDate'];

        //开发人员列表
        //$salers = array('刘珊珊', '宋现中', '王漫漫', '陈微微', '常金彩', '薛晨昕', '廖露露', '陈曦曦', '李星', '赵润连');
        $salersSql = "SELECT username FROM `user` u INNER JOIN auth_assignment a ON u.id=a.user_id WHERE a.item_name='产品开发' ORDER BY username";
        $saleList = Yii::$app->db->createCommand($salersSql)->queryAll();
        $salers = ArrayHelper::getColumn($saleList, 'username');
        $salers_str = implode(',', $salers);

        $sql = "EXEC z_demo_wo_test_sku_lirun '{$params['DateFlag']}','{$params['BeginDate']}','{$params['EndDate']}','{$params['CreateBeginDate']}','{$params['CreateEndDate']}','{$salers_str}'";
        $ret = Yii::$app->py_db->createCommand($sql)->queryAll();
        if ($ret === false) {
            return 'Error in executing statement.';
        }

        $SaleMoneyRmb = $SaleProfitRmb = $AllReport = $HotReport = $PopReport = [];
        foreach ($ret as $value) {
            if ($value['dataType'] === 'All') {
                //统计所有产品
                $item1 = $value;
                unset($item1['dataType']);
                $AllReport[] = $item1;
                //统计销售额和利润
                if (in_array($value['salername'], $salers)) {
                    $SaleMoneyRmb[$value['salername']] = $value['saleMoneyRmb'];
                    $SaleProfitRmb[$value['salername']] = $value['profitRmb'];
                }
            }

            if ($value['dataType'] === 'Hot') {
                //统计爆款产品
                $item2 = $value;
                unset($item2['dataType']);
                $HotReport[] = $item2;
            }

            if ($value['dataType'] === 'Pop') {
                //统计热销产品
                $item3 = $value;
                unset($item3['dataType']);
                $PopReport[] = $item3;
            }
        }
        return [
            'AllReport' => $AllReport,
            'HotReport' => $HotReport,
            'PopReport' => $PopReport,
            'SaleMoneyRmb' => $SaleMoneyRmb,
            'SaleProfitRmb' => $SaleProfitRmb
        ];
    }


    public static function getSalesChange($condition)
    {
        $data['suffix'] = $condition['suffix'];
        $data['pingtai'] = $condition['plat'];
        $data['salerName'] = $condition['salerName'];

        try {


            $today = date('Y-m-d');
            $date = Yii::$app->db->createCommand("SELECT updateDate FROM cache_salesChangeInTenDays WHERE updateDate >= '$today'")->queryOne();
            if (!$date) {
                //print_r($date);exit;
                Yii::$app->db->createCommand("TRUNCATE TABLE cache_salesChangeInTenDays")->execute();
                //$stmt = "EXEC z_demo_zongchange @suffix='$data[suffix]',@SalerName='$data[SalerName]',@pingtai='$data[pingtai]',@PageIndex='$condition[page]',@PageNum='$condition[pageSize]' ";
                $stmt = "EXEC z_demo_zongchange @suffix='',@SalerName='',@pingtai='' ";
                $list = Yii::$app->py_db->createCommand($stmt)->queryAll();
                //print_r($data);exit;
                $res = Yii::$app->db->createCommand()->batchInsert('cache_salesChangeInTenDays',
                    ['pingtai', 'suffix', 'goodsCode', 'goodsName', 'goodsSkuStatus', 'categoryName', 'salerName', 'salerName2', 'createDate',
                        'jinyitian', 'shangyitian', 'changeOneDay', 'jinwutian', 'shangwutian', 'changeFiveDay', 'jinshitian', 'shangshitian', 'changeTenDay', 'updateDate'],
                    $list)->execute();
                if ($res === false) {
                    throw new Exception("Error in executing statement.");
                }
            }
            $sql = "SELECT goodsCode,goodsName,goodsSKUStatus,categoryName,salerName,salerName2,createDate,
                SUM(jinyitian) AS jinyitian,
                SUM(shangyitian) AS shangyitian,
                SUM(changeOneDay) AS changeOneDay,
                SUM(jinwutian) AS jinwutian,
                SUM(shangwutian) AS shangwutian,
                SUM(changeFiveDay) AS changeFiveDay,
                SUM(jinshitian) AS jinshitian,
                SUM(shangshitian) AS shangshitian,
                SUM(changeTenDay) AS changeTenDay
                FROM cache_salesChangeInTenDays WHERE 1=1 ";
            if ($data['pingtai']) {
                $sql .= " AND pingtai='{$data['pingtai']}' ";
            }
            if ($data['salerName']) {
                $salerName = implode("','", $data['salerName']);;
                $sql .= " AND salerName IN ('{$salerName}' ";
            }
            if ($data['suffix']) {
                $suffix = implode("','", $data['suffix']);
                $sql .= " AND suffix IN ('{$suffix}') ";
            }
            $sql .= " GROUP BY goodsCode,goodsName,goodsSKUStatus,categoryName,salerName,salerName2,createDate";
            //print_r($sql);exit;
            $ret = Yii::$app->db->createCommand($sql)->queryAll();
            return new ArrayDataProvider([
                'allModels' => $ret,
                'pagination' => [
                    'pageSize' => isset($condition['pageSize']) && $condition['pageSize'] ? $condition['pageSize'] : 20,
                ],
            ]);
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }
    }


    public static function getLogisticsCost($condition)
    {
        $BeginDate = trim($condition['beginDate']);
        $EndDate = trim($condition['endDate']);
        $wlCompany = trim($condition['wlCompany']);

        $tsql = "EXEC P_Company_ExpressFare '{$BeginDate}','{$EndDate}','{$wlCompany}'";
        $res =  $ret = Yii::$app->py_db->createCommand($tsql)->queryAll();
        if( $res === false ) {
            echo "Error in executing statement.";
        }
        $arr = [];
        foreach ( $res as $row ) {
            if($row['wlCompany']=='汇总'){
                $arr['allfee'] = $row;
            }elseif($row['wlCompany']=='物流方式找不到物流公司'){
                $arr['red'] = $row;
            }else{
                $arr[] = $row;
            }
        }
//        dump($arr);exit;
        return $arr;
    }



}