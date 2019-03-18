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


use backend\models\OaSupplierOrder;
use backend\models\OaSupplierOrderDetail;
use yii\data\ActiveDataProvider;
use Yii;
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
            'query' => OaSupplierOrderDetail::find()->joinWith('oa_SupplierOrder')->where(['orderId' => $id])->select(
                'oa_SupplierOrderDetail.*,oa_SupplierOrder.billNumber'
            ),
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
            foreach ($condition as $detailId => $row) {
                $detail = OaSupplierOrderDetail::findOne(['id' => $detailId]);
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


}