<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-07-20
 * Time: 9:58
 */

namespace backend\modules\v1\models;

use backend\models\ShopElf\CGStockOrderM;
use backend\modules\v1\aliApi\AgentProductSimpleGet;
use backend\modules\v1\utils\Helper;
use Yii;
use yii\db\Exception;
use yii\helpers\ArrayHelper;

class ApiPurchaseTool
{
    /////////////////////////////清仓SKU/非清仓SKU/////////////////////////////
    public static function clearSku($is_normal = 0)
    {
        if (!$is_normal) {
            $select_sku = 'wo_test_purchasingBill @chkNoShowPur=1';
            $sql = " select DISTINCT bgs.sku from p_tradeun(nolock) as pt LEFT JOIN 
                   p_tradeDtUn(nolock) as ptd on pt.nid=ptd.tradenid LEFT JOIN 
                   b_goodssku(nolock) as bgs on bgs.sku=ptd.sku where addressowner in ('shopee','wish','joom')  
                   and protectionEligibilityType='缺货订单' and 
                   goodsSKUstatus in  (select DISTINCT skuStatus from y_mark_trades)";
        } else {
            $select_sku = 'wo_test_purchasingBill @chkNoShowPur=1 ,@isNormalSku=1';
            $sql = " select DISTINCT bgs.sku from p_tradeun(nolock) as pt LEFT JOIN 
                   p_tradeDtUn(nolock) as ptd on pt.nid=ptd.tradenid LEFT JOIN 
                   b_goodssku(nolock) as bgs on bgs.sku=ptd.sku where addressowner in  ('shopee','wish','joom' )  
                   and protectionEligibilityType='缺货订单' and goodsSKUstatus not in 
                   (select DISTINCT skuStatus from y_mark_trades)";
        }
        $sku_to_handle = Yii::$app->py_db->createCommand($select_sku)->queryAll();
        $cleaned_sku = Yii::$app->py_db->createCommand($sql)->queryAll();
        #转换成key-value
        $out = [];
        foreach ($cleaned_sku as $value) {
            foreach ($sku_to_handle as $row) {
                $key = $row['supplierId'];
                $skuList = explode(',', $row['allSku']);
                foreach ($skuList as $v) {
                    if ($value['sku'] == $v) {
                        if (array_key_exists($row['supplierId'], $out)) {
                            $out[$key] = $v . ',' . $out[$key];
                        } else {
                            $out[$key] = $v;
                        }
                        break 2;
                    }
                }
            }
        }
        return implode(',', array_values($out));
    }


    /////////////////////////////缺货管理/////////////////////////////
    public static function shortage()
    {
        $select_sku = 'wo_test_purchasingBill @chkNoShowPur=1';
        $sku_to_handle = Yii::$app->py_db->createCommand($select_sku)->queryAll();
        $sku = ArrayHelper::getColumn($sku_to_handle, 'allSku');
        return implode(',', $sku);
    }


    ///////////////////////////自动审核/同步差额///////////////////////////////

    /** 审核采购订单
     * Date: 2020-08-04 13:49
     * Author: henry
     * @return array
     * @throws Exception
     */
    public static function checkPurchaseOrder($check = true)
    {
        $orderList = self::getPurchaseOrderList($check);
//        var_dump($orderList);exit;
        $res = [];
        foreach ($orderList as $v) {
            $orderInfo = self::getOrderDetails($v);
            if ($orderInfo['order']) {
                $item = self::autoCheck($orderInfo['order'], $check);
            } else {
                $item = $orderInfo['message'];
            }
            $res[] = $item;
        }
//        var_dump($res);exit;
        return $res;
    }

    /** 获取采购订单
     * Date: 2020-08-04 10:32
     * Author: henry
     * @return mixed
     */
    public static function getPurchaseOrderList($check)
    {
        if ($check) {
            $sql = "SELECT billNumber,note,'caigoueasy' AS account
                FROM cg_stockorderm  AS cm WITH(nolock)
                LEFT JOIN S_AlibabaCGInfo AS info WITH(nolock) ON Cm.AliasName1688 = info.AliasName
                where checkflag=0 AND datediff(day,makedate,getdate())<4
                AND isnull(note,'') != '' -- and billNumber='CGD-2020-08-04-0207'
                Union
                SELECT billNumber,note,'caigoueasy' as account
                FROM cg_stockorderm  AS cm WITH(nolock)
                LEFT JOIN S_AlibabaCGInfo AS info WITH(nolock) ON Cm.AliasName1688 = info.AliasName
                WHERE alibabaorderid ='' AND DATEDIFF(dd, MakeDate, GETDATE()) BETWEEN 0 AND 4
                AND CheckFlag=1 AND Archive=0 AND InFlag=0 AND isnull(note,'') != '' ";
            $data = Yii::$app->py_db->createCommand($sql)->queryAll();
            foreach ($data as &$val) {
                $val['orderId'] = '';
                preg_match_all('/\d+/', $val['note'], $matches);
                foreach ($matches[0] as $v) {
                    if (strlen($v) > 10) {
                        $val['orderId'] = $v;
                        break;
                    }
                }
            }
        } else {
            $someDays = date('Y-m-d',strtotime('-20 day'));
            $sql = "SELECT DISTINCT billNumber,alibabaOrderid AS orderId,case when loginId like 'caigoueasy%' then 
                 'caigoueasy' else loginId end  AS account ,MakeDate 
                FROM CG_StockOrderD  AS cd WITH(nolock)  
                LEFT JOIN CG_StockOrderM  AS cm WITH(nolock) ON cd.stockordernid = cm.nid  
                LEFT JOIN S_AlibabaCGInfo AS info WITH(nolock) ON Cm.AliasName1688 = info.AliasName  
                LEFT JOIN B_GoodsSKU AS g WITH(nolock) ON cd.goodsskuid = g.nid  
                WHERE  CheckFlag=1 AND MakeDate > '{$someDays}'  AND isnull(loginId,'') LIKE 'caigoueasy%'  
                -- AND BillNumber = 'CGD-2020-07-13-2761' 
                AND StoreID IN (2,7,36) AND ABS(OrderMoney - alibabamoney) > 0.1 ORDER BY MakeDate";
            $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        }
        return $data;
    }

    /** 获取1688订单信息
     * @param $data
     * Date: 2020-08-04 11:33
     * Author: henry
     * @return array
     */
    public static function getOrderDetails($data)
    {
//        var_dump($data['billNumber']);
        $oauth = new AgentProductSimpleGet($data['account']);
        $params = [
            'webSite' => '1688',
            'orderId' => $data['orderId'],
            'access_token' => $oauth->token,
            'api_type' => 'com.alibaba.trade',
            'api_name' => 'alibaba.trade.get.buyerView'
        ];
        //var_dump($params);exit;
        $base_url = $oauth->get_request_url($params);
        $ret = Helper::curlRequest($base_url, [], [], 'GET');
        $message = $info = '';
        if (isset($ret['success'])) {
            if ($ret['success'] == 'true') {
                $out['orderId'] = $data['orderId'];
                $out['expressFee'] = $ret['result']['baseInfo']['shippingFee'];
                $out['sumPayment'] = $ret['result']['baseInfo']['totalAmount'];
                $out['qty'] = 0;
                foreach ($ret['result']['productItems'] as $v) {
                    $out['qty'] += $v['quantity'];
                }
                $info = $out;
            } else {
                $message = $ret['errorMessage'];
            }
        } else {
            $message = isset($ret['error_message']) ? $ret['error_message'] : 'Failed to get 1688 order info!';
        }
        return [
            'message' => $message,
            'order' => $info,
        ];
    }

    /**
     * @param $orderInfo
     * Date: 2020-08-04 13:49
     * Author: henry
     * @return string
     * @throws Exception
     */
    public static function autoCheck($orderInfo, $check)
    {
        $searchSql = "SELECT cgsm.nid,cgsm.billNumber,cgsm.recorder,cgsm.audier,cgsm.checkflag,
            cgsm.audiedate, sum(sd.amount) total_amt,
            sum(sd.amount * gs.costprice) AS total_cost_money, 
            sum(sd.amount*sd.price) AS total_money, cgsm.expressfee 
            FROM cg_stockorderd  AS sd WITH(nolock) 
            LEFT JOIN cg_stockorderm  AS cgsm WITH(nolock) ON sd.stockordernid= cgsm.nid 
            LEFT JOIN b_goodssku  AS gs ON sd.goodsskuid= gs.nid ";
        if($check){
            $orderId = '%' . $orderInfo['orderId'] . '%';
            $searchSql .= "WHERE note LIKE '{$orderId}' AND cgsm.checkflag = 0 ";
        }else{
            $orderId = $orderInfo['orderId'];
            $searchSql .= "WHERE alibabaOrderid = '{$orderId}' ";
        }
        $searchSql .= " GROUP BY cgsm.billNumber, cgsm.nid,cgsm.recorder,cgsm.expressfee,cgsm.audier,cgsm.audiedate,cgsm.checkflag";
        $ret = Yii::$app->py_db->createCommand($searchSql)->queryOne();
//        $ret = Yii::$app->db->createCommand($searchSql)->getRawSql();
//        var_dump($ret);exit;
        if (!$ret) {
            return 'No need to check ' . $orderInfo['orderId'];
        }
        if ($ret['total_amt'] == $orderInfo['qty']) {
            $message = self::updatePurchaseOrderInfo($ret, $orderInfo, $check);
        } else {
            $message = 'Quantity is not same of order ' . $orderInfo['orderId'];
        }
//        var_dump($message);exit;
        return $message;
    }

    /** 更新订单信息
     * @param $orderInfo
     * @param $checkInfo
     * Date: 2020-08-04 13:32
     * Author: henry
     * @return string
     * @throws Exception
     */
    public static function updatePurchaseOrderInfo($orderInfo, $checkInfo, $check)
    {
        $qty = $orderInfo['total_amt'];
        $totalCostMoney = $orderInfo['total_cost_money'];
        $billNumber = $orderInfo['billNumber'];
        $audier = $orderInfo['audier'];
        $orderId = $checkInfo['orderId'];
        $expressFee = $checkInfo['expressFee'];
        $orderMoney = $checkInfo['sumPayment'];
        $updateParams = [
            ':ordermoney' => $orderMoney,
            ':alibabaorderid' => $orderId,
            ':expressFee' => $expressFee,
            ':alibabamoney' => $orderMoney,
            ':billNumber' => $billNumber
        ];
        // 平均订单差额$key
        $aveMoney = ($orderMoney - $totalCostMoney) / $qty;
        if($check){
            $str = " 审核订单";
            $updateSql = "update cg_stockorderM set checkflag =1,isSubmit=1,is1688Order=1,audiedate=getdate(),
                        audier=:audier,ordermoney=:ordermoney,alibabaorderid=:alibabaorderid,
                        expressFee=:expressFee, alibabamoney=:alibabamoney where billNumber = :billNumber";
            $updateParams[':audier'] = $audier;
        }else{
            $str = " 同步1688差额";
            $updateSql = "update cg_stockorderM set ordermoney=:ordermoney,alibabaorderid=:alibabaorderid,
                        expressFee=:expressFee, alibabamoney=:alibabamoney where billNumber = :billNumber";
        }
        $updatePriceSql = "update cgd set money = gs.costprice * amount + amount * {$aveMoney},
            allmoney= gs.costprice * amount + amount * {$aveMoney}, cgd.beforeavgprice= gs.costprice, 
            cgd.price= gs.costprice, cgd.taxprice= gs.costprice + {$aveMoney}  
            from cg_stockorderd  as cgd LEFT JOIN B_goodsSku as gs on cgd.goodsskuid = gs.nid 
            LEFT JOIN cg_stockorderm as cgm on cgd.stockordernid= cgm.nid where billnumber='{$billNumber}'";
        $checkSql = "P_CG_UpdateStockOutOfByStockOrder '{$billNumber}'";
        $log = 'ur_cleaner ' . date('Y-m-d H:i:s') . $str;
        $logSql = "INSERT INTO CG_StockLogs(OrderType,OrderNID,Operator,Logs) VALUES('采购订单', {$orderInfo['nid']}, 'ur_cleaner','{$log}')";

        $trans = CGStockOrderM::getDb()->beginTransaction();
        try {
            Yii::$app->py_db->createCommand($updateSql)->bindValues($updateParams)->execute();
            Yii::$app->py_db->createCommand($updatePriceSql)->execute();
            Yii::$app->py_db->createCommand($checkSql)->execute();
            Yii::$app->py_db->createCommand($logSql)->execute();
            $trans->commit();
            $message = 'Checking order ' . $billNumber;
        } catch (Exception $e) {
            $trans->rollBack();
            $message = $e->getMessage();
        }
        return $message;
    }
}
