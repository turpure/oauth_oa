<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-24
 * Time: 13:35
 */

namespace backend\modules\v1\models;

use Yii;

class ApiPerform
{

    public static function getNewProductDevelopmentPerformance($condition)
    {
        $params['DateFlag'] = 0;//0表交易时间
        $params['BeginDate'] = $condition['BeginDate'];
        $params['EndDate'] = $condition['EndDate'];
        $params['CreateBeginDate'] = $condition['CreateBeginDate'];
        $params['CreateEndDate'] = $condition['CreateEndDate'];

        //日期为空的话不能显示表了 可以前端验证 required
        if (empty($params['BeginDate']) || empty($params['EndDate'])) {
            return [
                'code' => 400,
                'message' => 'BeginDate and EndDate can not be empty！',
            ];
        }


        //$this->assign('params', $params);

        // 不用ＰＤＯ　,,,用sqlsrv 试试
        $serverName = "121.196.233.153,12580";
        $connectionInfo = array("UID" => "sa", "PWD" => "allroot89739659", "Database" => "ShopElf", "CharacterSet" => "utf-8");
        $conn = sqlsrv_connect($serverName, $connectionInfo);

        //开发人员列表
        //$salers = array('刘珊珊', '宋现中', '王漫漫', '陈微微', '常金彩', '薛晨昕', '廖露露', '陈曦曦', '李星', '赵润连');
        $salers = array('刘珊珊', '宋现中', '王漫漫', '陈微微', '常金彩', '薛晨昕', '廖露露', '陈曦曦', '李星', '赵润连');
        $salers_str = implode(',', $salers);

        //参数需要以如下数组方式赋值并标明类型，SQLSRV_PARAM_IN是输入类型，SQLSRV_PARAM_OUT是输出类型。注意要按照存储过程定义的顺序赋值

        $DateFlag = $params['DateFlag'];
        $BeginDate = $params['BeginDate'];
        $EndDate = $params['EndDate'];
        $CreateBeginDate = $params['CreateBeginDate'];
        $CreateEndDate = $params['CreateEndDate'];

        $pars = array(
            array($DateFlag, SQLSRV_PARAM_IN),
            array($BeginDate, SQLSRV_PARAM_IN),
            array($EndDate, SQLSRV_PARAM_IN),
            array($CreateBeginDate, SQLSRV_PARAM_IN),
            array($CreateEndDate, SQLSRV_PARAM_IN),
            array($salers_str, SQLSRV_PARAM_IN)
        );

        $sql = "{call z_demo_wo_test_sku_lirun(?,?,?,?,?,?)}";
        $ret = sqlsrv_query($conn, $sql, $pars);
        $ret = Yii::$app->db->createCommand($sql)->queryAll();

        print_r($ret);exit;
        if ($ret === false) {
            echo "Error in executing statement.\\n";
            die(print_r(sqlsrv_errors(), true));
        }

        $SaleMoneyRmb = array();
        $SaleProfitRmb = array();
        $AllReport = array();
        $HotReport = array();
        $PopReport = array();
        while ($row = sqlsrv_fetch_array($ret, SQLSRV_FETCH_ASSOC)) {//返回结果集 GoodsSKUStatus
//            $row['salername'] = iconv('GBK', 'UTF-8', $row['salername']);
            if ($row['dataType'] === 'All') {

                //统计所有产品
                unset($row['dataType']);
                $AllReport[] = $row;

                //统计销售额和利润
                if (in_array($row['salername'], $salers)) {
                    $SaleMoneyRmb[$row['salername']] += $row['saleMoneyRmb'];
                    $SaleProfitRmb[$row['salername']] += $row['profitRmb'];

                }
            }

            if ($row['dataType'] === 'Hot') {
                //统计爆款产品
                unset($row['dataType']);
                $HotReport[] = $row;
            }

            if ($row['dataType'] === 'Pop') {
                //统计热销产品
                unset($row['dataType']);
                $PopReport[] = $row;
            }

        }
//        var_dump($SaleMoneyRmb);die;
        //$this->assign('SaleMoneyRmb', $SaleMoneyRmb);
        //$this->assign('SaleProfitRmb', $SaleProfitRmb);
        //$this->assign('AllReport', $AllReport);
        //$this->assign('HotReport', $HotReport);
        //$this->assign('PopReport', $PopReport);
        //$this->display('table');
        return $PopReport;

    }


}