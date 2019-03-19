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
        $sql = "SELECT 	om.nid,om.BillNumber,om.CHECKfLAG,s.SupplierName,om.MakeDate,om.Recorder,om.DelivDate,om.OrderAmount,om.OrderMoney 
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
        if ($condition['daterange']) {
            $date = explode(' - ', $condition['daterange']);
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
        $sql = "select m.NID,d.GoodsID,s.Goodscode,s.Goodsname,gs.SKU, 
			    s.Class,s.Model,gs.property1,gs.property2,gs.property3,s.Unit,
                d.amount,d.price,d.money,d.TaxRate,d.TaxMoney,d.AllMoney,s.BMPFileName,
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
        if(!$ids) return false;
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
                foreach ($detail as $v) {
                    $detailModel = new OaSupplierOrderDetail();
                    $detailModel->orderId = $orderModel->id;
                    $detailModel->sku = $v['SKU'];
                    $detailModel->goodsCode = $v['Goodscode'];
                    $detailModel->image = $v['BmpFileName'];
                    $detailModel->goodsName = $v['Goodsname'];
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
     * Date: 2019-03-19 15:58
     * Author: henry
     * @return string
     */
    public static function sync($ids)
    {
        $db = Yii::$app->py_db;
        $trans = $db->beginTransaction();
        try {
            foreach ($ids as $id) {
                $billNumber = OaSupplierOrder::findOne($id)['billNumber'];
                $detailList = OaSupplierOrderDetail::findAll(['orderId' => $id]);
                //获取要更新的信息
                $sql = "SELECT billNumber,orderAmount AS totalNumber,CASE WHEN CheckFlag = 0 THEN '未审核'ELSE '已审核' END AS billStatus 
                FROM CG_StockOrderM WHERE billNumber='{$billNumber}'";
                $res = $db->createCommand($sql)->queryOne();
                if (!$res) {
                    throw new \Exception('同步失败！');
                }
                //更新订单
                $res = Yii::$app->db->createCommand()->update(OaSupplierOrder::tableName(),$res,['id' => $id])->execute();
                if (!$res) {
                    throw new \Exception('同步失败！');
                }
                if($detailList){
                    foreach ($detailList as $v){
                        $detailSql = "SELECT amount AS purchaseNumber,price AS purchasePrice,sku
                        FROM CG_StockOrderD cgd LEFT JOIN CG_StockOrderM cgm ON cgm.nid = cgd.StockOrderNID 
                        INNER JOIN B_goodsSKu AS bgs ON bgs.nid = cgd.GoodsSKUID
                        WHERE billNumber='{$billNumber}' AND bgs.sku='{$v['sku']}'";
                        $res = $db->createCommand($detailSql)->queryOne();
                        if (!$res) {
                            throw new \Exception('同步失败！');
                        }
                        //更新订单
                        $res = Yii::$app->db->createCommand()->update(OaSupplierOrderDetail::tableName(),$res,['id' => $v['id']])->execute();
                        if (!$res) {
                            throw new \Exception('同步失败！');
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


}