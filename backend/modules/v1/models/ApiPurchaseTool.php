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

class ApiPurchaseTool
{

    /** 审核采购订单
     * Date: 2020-08-04 13:49
     * Author: henry
     * @return array
     * @throws Exception
     */
    public static function checkPurchaseOrder()
    {
        $orderList = self::getPurchaseOrderList();
        $res = [];
        foreach ($orderList as $v) {
            $orderInfo = self::getOrderDetails($v);
            if ($orderInfo['order']) {
                $item = self::autoCheck($orderInfo['order']);
            } else {
                $item = $orderInfo['message'];
            }
            $res[] = $item;
        }
//        var_dump($res);exit;
        return $res;
    }

    /** 获取未审核采购订单
     * Date: 2020-08-04 10:32
     * Author: henry
     * @return mixed
     */
    public static function getPurchaseOrderList()
    {
        $sql = "select billNumber,note,'caigoueasy'as account
            from cg_stockorderm  as cm with(nolock)
            LEFT JOIN S_AlibabaCGInfo as info with(nolock) on Cm.AliasName1688 = info.AliasName
            where checkflag=0 and datediff(day,makedate,getdate())<4
            and isnull(note,'') != '' -- and billNumber='CGD-2020-08-04-0207'
            Union
            select billNumber,note,'caigoueasy' as account
            from cg_stockorderm  as cm with(nolock)
            LEFT JOIN S_AlibabaCGInfo as info with(nolock) on Cm.AliasName1688 = info.AliasName
            where alibabaorderid ='' and DATEDIFF(dd, MakeDate, GETDATE()) BETWEEN 0 and 4
            and CheckFlag=1 and Archive=0 and InFlag=0 and isnull(note,'') != '' ";
        $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        foreach ($data as &$val) {
            preg_match_all('/\d+/', $val['note'], $matches);
            foreach ($matches[0] as $v) {
                if (strlen($v) > 10) {
                    $val['orderId'] = $v;
                    break;
                }
            }
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
        $oauth = new AgentProductSimpleGet($data['account']);
        $params = [
            'webSite' => '1688',
            'orderId' => $data['orderId'],
            'access_token' => $oauth->token,
            'api_type' => 'com.alibaba.trade',
            'api_name' => 'alibaba.trade.get.buyerView'
        ];
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
            $message = $ret['error_message'];
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
    public static function autoCheck($orderInfo)
    {
        $orderId = '%'.$orderInfo['orderId'].'%';
        $searchSql = "select cgsm.nid,cgsm.billNumber,cgsm.recorder,cgsm.audier,cgsm.checkflag,
            cgsm.audiedate, sum(sd.amount) total_amt,
            sum(sd.amount * gs.costprice) as total_cost_money, 
            sum(sd.amount*sd.price) as total_money, cgsm.expressfee 
            from cg_stockorderd  as sd with(nolock) 
            LEFT JOIN cg_stockorderm  as cgsm with(nolock) on sd.stockordernid= cgsm.nid 
            LEFT JOIN b_goodssku  as gs on sd.goodsskuid= gs.nid 
            where note like '{$orderId}' 
            and cgsm.checkflag =0 GROUP BY cgsm.billNumber, cgsm.nid,cgsm.recorder,
            cgsm.expressfee,cgsm.audier,cgsm.audiedate,cgsm.checkflag";
        $ret = Yii::$app->py_db->createCommand($searchSql)->queryOne();
//        $ret = Yii::$app->db->createCommand($searchSql)->getRawSql();
        if (!$ret) {
            return 'no need to check ' . $orderInfo['orderId'];
        }
        if ($ret['total_amt'] == $orderInfo['qty']){
            $message = self::updatePurchaseOrderInfo($ret, $orderInfo);
        }else{
            $message = 'quantity is not same of order ' . $orderInfo['orderId'];
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
    public static function updatePurchaseOrderInfo($orderInfo, $checkInfo){
        $qty = $orderInfo['total_amt'];
        $totalCostMoney = $orderInfo['total_cost_money'];
        $billNumber = $orderInfo['billNumber'];
        $audier = $orderInfo['audier'];
        $orderId = $checkInfo['orderId'];
        $expressFee = $checkInfo['expressFee'];
        $orderMoney = $checkInfo['sumPayment'];
//        var_dump($orderId);exit;
        // 平均订单差额
        $aveMoney = ($orderMoney - $totalCostMoney)/$qty;
        $checkSql = "P_CG_UpdateStockOutOfByStockOrder '{$billNumber}'";
        $log = 'ur_cleaner ' . date('Y-m-d H:i:s') . " 审核订单";
        $logSql = "INSERT INTO CG_StockLogs(OrderType,OrderNID,Operator,Logs) VALUES('采购订单', {$orderInfo['nid']}, 'ur_cleaner','{$log}')";
//        $update_status = "update cg_stockorderM  set checkflag =1, audier=%s,ordermoney=%s,audiedate=getdate() where billNumber = %s";
        $updateSql = "update cg_stockorderM set checkflag =1,isSubmit=1,is1688Order=1,audiedate=getdate(),
                        audier=:audier,ordermoney=:ordermoney,alibabaorderid=:alibabaorderid,
                        expressFee=:expressFee, alibabamoney=:alibabamoney where billNumber = :billNumber";
        $updatePriceSql = "update cgd set money = gs.costprice * amount + amount * {$aveMoney},
            allmoney= gs.costprice * amount + amount * {$aveMoney}, cgd.beforeavgprice= gs.costprice, 
            cgd.price= gs.costprice, cgd.taxprice= gs.costprice + {$aveMoney}  
            from cg_stockorderd  as cgd LEFT JOIN B_goodsSku as gs on cgd.goodsskuid = gs.nid 
            LEFT JOIN cg_stockorderm as cgm on cgd.stockordernid= cgm.nid where billnumber='{$billNumber}'";
        $updateParams = [
            ':audier' => $audier,
            ':ordermoney' => $orderMoney,
            ':alibabaorderid' => $orderId,
            ':expressFee' => $expressFee,
            ':alibabamoney' => $orderMoney,
            ':billNumber' => $billNumber
        ];
        $trans = CGStockOrderM::getDb()->beginTransaction();
        try{
            Yii::$app->py_db->createCommand($updateSql)->bindValues($updateParams)->execute();
            Yii::$app->py_db->createCommand($updatePriceSql)->execute();
            Yii::$app->py_db->createCommand($checkSql)->execute();
            Yii::$app->py_db->createCommand($logSql)->execute();
            $trans->commit();
            $message = 'Checking order ' . $billNumber;
        }catch (Exception $e){
            $trans->rollBack();
            $message = $e->getMessage();
        }
        return $message;
    }


}
