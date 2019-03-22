<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-03-15
 * Time: 14:46
 * Author: henry
 */

/**
 * @name ApiSupplierOrder.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-03-15 14:46
 */


namespace backend\modules\v1\models;


use backend\models\OaSupplier;
use backend\models\OaSupplierOrder;
use backend\models\OaSupplierOrderDetail;
use backend\models\OaSupplierOrderPaymentDetail;
use backend\modules\v1\utils\Handler;
use yii\data\ActiveDataProvider;
use Yii;
use yii\data\ArrayDataProvider;
use yii\db\Query;

class ApiSupplierOrder
{

    /**
     * @param $condition
     * Date: 2019-03-15 16:06
     * Author: henry
     * @return ActiveDataProvider
     */
    public static function getOaSupplierOrderList($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 20;
        $query = OaSupplierOrder::find();

        if (isset($condition['syncTime'])) $query->andFilterWhere(['syncTime' => $condition['syncTime']]);
        if (isset($condition['amt'])) $query->andFilterWhere(['amt' => $condition['amt']]);
        if (isset($condition['paymentAmt'])) $query->andFilterWhere(['paymentAmt' => $condition['paymentAmt']]);
        if (isset($condition['unpaidAmt'])) $query->andFilterWhere(['unpaidAmt' => $condition['unpaidAmt']]);

        if (isset($condition['billNumber'])) $query->andFilterWhere(['like', 'billNumber', $condition['billNumber']]);
        if (isset($condition['supplierName'])) $query->andFilterWhere(['like', 'supplierName', $condition['supplierName']]);
        if (isset($condition['billStatus'])) $query->andFilterWhere(['like', 'billStatus', $condition['billStatus']]);
        if (isset($condition['purchaser'])) $query->andFilterWhere(['like', 'purchaser', $condition['purchaser']]);

        if (isset($condition['deliveryStatus'])) $query->andFilterWhere(['like', 'deliveryStatus', $condition['deliveryStatus']]);
        if (isset($condition['expressNumber'])) $query->andFilterWhere(['like', 'expressNumber', $condition['expressNumber']]);
        if (isset($condition['paymentStatus'])) $query->andFilterWhere(['like', 'paymentStatus', $condition['paymentStatus']]);

        $query->orderBy('id DESC');

        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }

    /**
     * @param $condition
     * Date: 2019-03-16 16:50
     * Author: henry
     * @return array|ActiveDataProvider
     */
    public static function getOaSupplierOrderInfo($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            return [];
        }

        $orderDetail = new ActiveDataProvider([
            'query' => (new Query())
                ->select('so.*,os.billNumber')
                ->from('proCenter.oa_supplierOrderDetail so')
                ->leftJoin('proCenter.oa_supplierOrder os', 'os.id=orderId')
                ->where(['orderId' => $id]),
            'pagination' => ['pageSize' => 200]
        ]);
        $sort = $orderDetail->sort;
        $sort->attributes['billNumber'] = ['asc' => ['billNumber' => SORT_ASC], 'desc' => ['billNumber' => SORT_DESC]];
        $orderDetail->sort = $sort;
        return $orderDetail;
    }


    /**
     * @param $condition
     * Date: 2019-03-16 16:56
     * Author: henry
     * @return bool|string
     * @throws \yii\db\Exception
     */
    public static function saveOaSupplierOrderInfo($condition)
    {
        $trans = Yii::$app->db->beginTransaction();
        try {
            foreach ($condition as $row) {
                $detail = OaSupplierOrderDetail::findOne(['id' => $row['id']]);
                //print_r($detail);exit;
                if (!empty($detail)) {
                    $detail->setAttributes($row);
                    if (!$detail->save()) {
                        throw new \Exception('fail to save order details');
                    }
                }
            }
            $msg = true;
            $trans->commit();
        } catch (\Exception $why) {
            $trans->rollBack();
            $msg = $why->getMessage();
        }
        return $msg;
    }

    /**
     * 获取普源采购订单列表
     * @param $condition
     * Date: 2019-03-19 10:44
     * Author: henry
     * @return ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public static function getPyOrderList($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 20;
        $sql = "SELECT 	om.nid,om.billNumber,om.checkFlag,s.supplierName,om.makeDate,om.recorder,om.delivDate,om.orderAmount,om.orderMoney 
                    FROM [dbo].[CG_StockOrderM](nolock) om
                    LEFT JOIN  CG_StockOrderD od ON od.StockOrderNID=om.NID
                    LEFT JOIN B_Goods b ON b.NID=od.GoodsID
                    LEFT JOIN B_GoodsSKU bs ON bs.NID=od.GoodsSKUID 
                    LEFT JOIN B_Supplier s ON s.NID=om.SupplierID
                    WHERE CHECKfLAG=0 ";
        //筛选供应商
        if ($condition['supplierName']) {
            $sql .= " AND s.SupplierName LIKE '%" . $condition['supplierName'] . "%'";
        }
        //筛选订单时间
        if ($condition['dateRange']) {
            //$date = explode(' - ', $condition['daterange']);
            $date = $condition['dateRange'];
            $sql .= " AND om.MakeDate BETWEEN '" . $date[0] . "' AND '" . $date[1] . " 23:59:59'";
        }
        //筛选订单号
        if ($condition['billNumber']) {
            $sql .= " AND om.billNumber LIKE '%" . $condition['billNumber'] . "%'";
        }
        //筛选商品编码
        if ($condition['goodsCode']) {
            $sql .= " AND b.goodsCode LIKE '%" . $condition['goodsCode'] . "%'";
        }
        //筛选SKU
        if ($condition['sku']) {
            $sql .= " AND bs.sku LIKE '%" . $condition['sku'] . "%'";
        }
        //过滤未同步的数据
        //$sql .= " AND NOT EXISTS (SELECT billNumber FROM oa_supplierOrder WHERE oa_supplierOrder.billNumber=om.BillNumber)";
        $sql .= " GROUP BY om.nid,om.BillNumber,om.CHECKfLAG,s.SupplierName,om.MakeDate,om.Recorder,om.DelivDate,om.OrderAmount,om.OrderMoney";

        $list = Yii::$app->py_db->createCommand($sql)->queryAll();
        $dataProvider = new ArrayDataProvider([
            'allModels' => $list,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
            'sort' => [
                'attributes' => ['BillNumber', 'CHECKfLAG', 'SupplierName', 'MakeDate', 'Recorder', 'DelivDate', 'OrderAmount', 'OrderMoney'],
            ],
        ]);
        return $dataProvider;
    }


    /** 获取采购订单详情列表
     * @param $condition
     * Date: 2019-03-19 13:22
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
    public static function getPyOrderDetail($id)
    {
        $sql = "select m.nid,d.goodsID,s.goodsCode,s.goodsName,gs.sku, 
			    s.class,s.model,gs.property1,gs.property2,gs.property3,s.unit,
                d.amount,d.price,d.money,d.taxRate,d.taxMoney,d.allMoney,s.bmpFileName
                      from CG_StockOrderD(nolock) d  
                      inner join  CG_StockOrderM(nolock) m on m.NID=d.StockOrderNID  
                      inner join B_GoodsSKU(nolock) gs on gs.NID=d.GoodsSKUID  
                      inner join B_Goods(nolock) s on s.NID=gs.GoodsID 
                      where m.NID = {$id} order by m.nid,gs.SKU";

        return Yii::$app->py_db->createCommand($sql)->queryAll();
    }


    /** 同步采购订单到系统表
     * @param $ids
     * Date: 2019-03-19 14:03
     * Author: henry
     * @return bool|string
     * @throws \yii\db\Exception
     */
    public static function syncPyOrders($ids)
    {
        if (!$ids) return false;
        $trans = Yii::$app->db->beginTransaction();
        try {
            foreach ($ids as $id) {
                //获取订单信息
                $orderSql = "SELECT om.billNumber,CASE WHEN om.CHECKfLAG=1 THEN '已审核' ELSE '未审核' END AS billStatus,
                            s.supplierName,om.MakeDate AS orderTime,om.OrderAmount AS totalNumber,om.OrderMoney AS amt
                    FROM [dbo].[CG_StockOrderM](nolock) om 
                    LEFT JOIN B_Supplier s ON s.NID=om.SupplierID
                    WHERE om.nid=" . $id;
                $res = Yii::$app->py_db->createCommand($orderSql)->queryOne();
                //根据订单供应商获取线下采购
                $user = Yii::$app->user->identity->username;
                if ($res['supplierName']) {
                    $supplierModel = OaSupplier::findOne(['supplierName' => $res['supplierName']]);
                    $purchaser = $supplierModel ? $supplierModel['purchase'] : '';
                    if ($purchaser !== $user) {
                        throw new \Exception('非名下供应商，同步失败！');
                    }
                }
                if ($res['billNumber']) {
                    $supplierModel = OaSupplierOrder::findOne(['billNumber' => $res['billNumber']]);
                    if ($supplierModel['billNumber'] == $res['billNumber'] ) {
                        throw new \Exception('该订单已经存在，请勿重复同步！');
                    }
                }
                $orderModel = new OaSupplierOrder();
                $orderModel->attributes = $res;
                $orderModel->totalNumber = (int)$res['totalNumber'];
                $orderModel->purchaser = $purchaser;
                $orderModel->syncTime = date('Y-m-d H:i:s');
                $res = $orderModel->save();
                if (!$res) {
                    throw new \Exception('同步失败!');
                }
                //保存订单明细
                $detail = self::getPyOrderDetail($id);
                //print_r($detail);exit;
                foreach ($detail as $v) {
                    $detailModel = new OaSupplierOrderDetail();
                    $detailModel->orderId = $orderModel->id;
                    $detailModel->sku = $v['sku'];
                    $detailModel->goodsCode = $v['goodsCode'];
                    $detailModel->image = $v['bmpFileName'];
                    $detailModel->goodsName = $v['goodsName'];
                    $detailModel->property1 = $v['property1'];
                    $detailModel->property2 = $v['property2'];
                    $detailModel->property3 = $v['property3'];
                    $detailModel->purchaseNumber = (int)$v['amount'];
                    $detailModel->purchasePrice = $v['price'];
                    $result = $detailModel->save();
                    if (!$result) {
                        throw new \Exception('同步失败！');
                    }
                }
            }
            $trans->commit();
            $res = true;
        } catch (\Exception $e) {
            $trans->rollBack();
            $res = [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
        return $res;
    }


    /** 手动同步普源订单信息
     * @param $ids
     * Date: 2019-03-22 11:02
     * Author: henry
     * @return array|bool
     */
    public static function sync($ids)
    {
        $db = Yii::$app->py_db;
        $trans = $db->beginTransaction();
        try {
            foreach ($ids as $id) {
                $supplierModel = OaSupplierOrder::findOne($id);
                $detailList = OaSupplierOrderDetail::findAll(['orderId' => $id]);
                //获取要更新的信息
                $sql = "SELECT billNumber,cast(OrderAmount AS INTEGER) AS totalNumber,CASE WHEN CheckFlag = 0 THEN '未审核'ELSE '已审核' END AS billStatus 
                FROM CG_StockOrderM WHERE billNumber='{$supplierModel['billNumber']}'";
                $res = $db->createCommand($sql)->queryOne();
                if (!$res) {
                    throw new \Exception('获取普源订单失败！');
                }
                //更新订单
                $supplierModel->attributes = $res;
                if (!$supplierModel->save()) {
                    throw new \Exception('同步订单失败！');
                }
                if ($detailList) {
                    foreach ($detailList as $v) {
                        $detailSql = "SELECT CAST(amount AS INTEGER) AS purchaseNumber,price AS purchasePrice,sku
                        FROM CG_StockOrderD cgd LEFT JOIN CG_StockOrderM cgm ON cgm.nid = cgd.StockOrderNID 
                        INNER JOIN B_goodsSKu AS bgs ON bgs.nid = cgd.GoodsSKUID
                        WHERE billNumber='{$supplierModel['billNumber']}' AND bgs.sku='{$v['sku']}'";
                        $res = $db->createCommand($detailSql)->queryOne();
                        if (!$res) {
                            throw new \Exception('获取普源订单详情失败！');
                        }
                        //print_r($res);exit;
                        //更新订单
                        $bb = Yii::$app->db->createCommand()->update(OaSupplierOrderDetail::tableName(), $res, ['id' => $v['id']])->execute();
                        $detailModel = OaSupplierOrderDetail::findOne(['id' => $v['id']]);
                        if (!$detailModel->save()) {
                            throw new \Exception('同步订单详情失败！');
                        }
                    }
                }
            }
            $trans->commit();
            $msg = true;
        } catch (\Exception $why) {
            $trans->rollBack();
            $msg = [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
        return $msg;

    }


    /** 请求付款
     * @param $condition
     * Date: 2019-03-20 17:15
     * Author: henry
     * @return array|bool
     * @throws \yii\db\Exception
     */
    public static function pay($condition)
    {
        $id = isset($condition['id']) && $condition['id'] ? $condition['id'] : 0;
        if (!$id) return false;
        $order = OaSupplierOrder::findOne(['id' => $id]);
        $paymentAmt = (float)trim($condition['number']);
        $payment = new OaSupplierOrderPaymentDetail();
        $db = Yii::$app->db;
        $trans = $db->beginTransaction();
        try {
            //保存订单付款状态
            $order->paymentStatus = '请求付款中';
            //保存付款明细
            $payment->billNumber = $order->billNumber;
            $payment->requestAmt = $paymentAmt;
            $payment->requestTime = date('Y-m-d H:i:s');
            $payment->paymentStatus = '未付款';

            if (!($order->save() && $payment->save())) {
                throw new \Exception('fail to save data!');
            }
            $trans->commit();
            //发送邮件给财务
            //$payment->send($id);//TODO
            $msg = true;
        } catch (\Exception $why) {
            $trans->rollBack();
            $msg = [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
        return $msg;
    }

    /** 付款明细（单个采购订单）
     * @param $condition
     * Date: 2019-03-20 17:15
     * Author: henry
     * @return bool|ActiveDataProvider
     */
    public static function payment($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : 0;
        if (!$id) return false;
        $order = OaSupplierOrder::findOne($id);
        $billNumber = $order ? $order['billNumber'] : '暂无数据';
        $orderDetail = new ActiveDataProvider([
            'query' => OaSupplierOrderPaymentDetail::find()
                ->where(['billNumber' => $billNumber]),
            'pagination' => ['pageSize' => 200]
        ]);
        return $orderDetail;
    }


    /** 付款明细（全部）
     * @param $condition
     * Date: 2019-03-20 17:15
     * Author: henry
     * @return bool|ActiveDataProvider
     */
    public static function getPaymentList($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 20;
        $query = OaSupplierOrderPaymentDetail::find();
        if (isset($condition['billNumber'])) $query->andFilterWhere(['like', 'billNumber', $condition['billNumber']]);
        if (isset($condition['paymentStatus'])) $query->andFilterWhere(['like', 'paymentStatus', $condition['paymentStatus']]);
        if (isset($condition['comment'])) $query->andFilterWhere(['like', 'comment', $condition['comment']]);
        if (isset($condition['requestTime'])) $query->andFilterWhere(['requestTime' => $condition['requestTime']]);
        if (isset($condition['paymentTime'])) $query->andFilterWhere(['paymentTime' => $condition['paymentTime']]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => $pageSize]
        ]);
        return $dataProvider;
    }

    /** 保存财务付款信息
     * @param $condition
     * Date: 2019-03-21 9:54
     * Author: henry
     * @return array|bool
     * @throws \yii\db\Exception
     */
    public static function savePaymentInfo($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (!$id) return false;
        $model = OaSupplierOrderPaymentDetail::findOne($id);
        $oldImg = $model['img'];
        //查找订单金额
        $order = OaSupplierOrder::findOne(['billNumber' => $model['billNumber']]);
        $totalAmt = $order['amt'];
        $db = Yii::$app->db;
        $trans = $db->beginTransaction();
        try {
            $img = Handler::common($condition['img'], 'payment');
            $img = $img ? (Yii::$app->request->hostInfo . '/' . $img) : '';

            $model->img = $img ? $img : $oldImg;
            $model->comment = isset($condition['comment']) ? $condition['comment'] : '';
            $model->paymentAmt = $condition['paymentAmt'];
            $model->paymentStatus = '已支付';
            $model->paymentTime = date('Y-m-d H:i:s');
            if (!$model->save()) {
                throw new \Exception('fail to save payment data!');
            }
            //保存订单信息
            //计算未付金额
            $sql = "SELECT SUM(IFNULL(paymentAmt,0)) AS paymentAmt FROM proCenter.oa_supplierOrderPaymentDetail 
                        WHERE billNumber='{$model['billNumber']}' AND paymentStatus='已支付'";
            $amt = Yii::$app->db->createCommand($sql)->queryOne();
            $order->unpaidAmt = $totalAmt - $amt['paymentAmt'] >= 0 ? $totalAmt - $amt['paymentAmt'] : 0;
            $order->paymentAmt = $amt['paymentAmt'];
            $order->paymentStatus = $amt['paymentAmt'] >= $totalAmt ? '全部付款' : '部分付款';
            $order->updatedTime = date('Y-m-d H:i:s');
            if (!$order->save()) {
                throw new \Exception('fail to save order data!');
            }
            $trans->commit();
            $msg = true;
        } catch (\Exception $e) {
            $trans->rollBack();
            $msg = [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }
        return $msg;
    }

    /** 发货
     * @param $condition
     * Date: 2019-03-21 9:58
     * Author: henry
     * @return bool
     * @throws \yii\db\Exception
     */
    public static function delivery($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (!$id) return false;
        $numbers = $condition['number'];
        $model = OaSupplierOrder::findOne($id);
        $oldNumber = $model->expressNumber;
        $model->expressNumber = $numbers?$numbers:$oldNumber;
        if (!$model->save()) {
            return false;
        }
        return true;
    }


    /** 导入物流单号到普源
     * @param $condition
     * Date: 2019-03-21 10:05
     * Author: henry
     * @return array|bool
     * @throws \yii\db\Exception
     */
    public static function inputExpress($condition)
    {
        $ids = isset($condition['ids'])?$condition['ids']:[];
        if (!$ids) return false;
        $db = Yii::$app->db;
        $trans = $db->beginTransaction();
        try {
            foreach ($ids as $key) {
                $order = OaSupplierOrder::findOne($key);
                $billNumber = $order->billNumber;
                $expressNumber = $order->expressNumber;
                $sql = "UPDATE cg_stockOrderM  SET logisticOrderNo='{$expressNumber}' WHERE BillNumber='{$billNumber}'";
                $res = Yii::$app->py_db->createCommand($sql)->execute();
                if (!$res) {
                    throw new \Exception('导入失败！');
                }
            }
            $trans->commit();
            $msg = true;
        } catch (\Exception $why) {
            $trans->rollBack();
            $msg = [
                'code' => 400,
                'message' => $why->getMessage(),
            ];
        }
        return $msg;
    }

    /**
     * 审核订单
     * @param $condition
     * Date: 2019-03-21 11:27
     * Author: henry
     * @return array|bool
     * @throws \yii\db\Exception
     */
    public static function check($condition)
    {
        $ids = isset($condition['ids'])?$condition['ids']:[];
        if (!$ids) return false;
        $db = Yii::$app->db;
        $trans = $db->beginTransaction();
        try {
            foreach ($ids as $key) {
                $order = OaSupplierOrder::findOne(['id' => $key]);
                $billNumber = $order->billNumber;
                $order->billStatus = '已审核';
                $sql = 'UPDATE CG_StockOrderM  SET CheckFlag=1 WHERE BillNumber=:billNumber';
                $res = $db->createCommand($sql, [':billNumber' => $billNumber])->execute();
                if (!$res || !$order->save()) {
                    throw new \Exception('审核失败！');
                }
            }
            $trans->commit();
            $msg = true;
        } catch (\Exception $why) {
            $trans->rollBack();
            $msg = [
                'code' => 400,
                'message' => $why->getMessage(),
            ];
        }
        return $msg;
    }


}