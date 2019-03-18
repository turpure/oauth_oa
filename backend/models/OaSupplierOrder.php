<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_supplierOrder".
 *
 * @property int $id
 * @property string $supplierName
 * @property string $billNumber
 * @property string $billStatus
 * @property string $purchaser
 * @property string $syncTime
 * @property int $totalNumber
 * @property string $amt
 * @property string $paymentStatus
 * @property string $orderTime
 * @property string $updatedTime
 * @property string $deliveryStatus
 * @property string $expressNumber
 * @property string $goodsName
 * @property string $paymentAmt
 * @property string $unpaidAmt
 */
class OaSupplierOrder extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_supplierOrder';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['syncTime', 'orderTime', 'updatedTime'], 'safe'],
            [['totalNumber'], 'integer'],
            [['amt', 'paymentAmt', 'unpaidAmt'], 'number'],
            [['supplierName', 'billNumber', 'billStatus', 'purchaser'], 'string', 'max' => 255],
            [['paymentStatus', 'deliveryStatus', 'expressNumber', 'goodsName'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'supplierName' => 'Supplier Name',
            'billNumber' => 'Bill Number',
            'billStatus' => 'Bill Status',
            'purchaser' => 'Purchaser',
            'syncTime' => 'Sync Time',
            'totalNumber' => 'Total Number',
            'amt' => 'Amt',
            'paymentStatus' => 'Payment Status',
            'orderTime' => 'Order Time',
            'updatedTime' => 'Updated Time',
            'deliveryStatus' => 'Delivery Status',
            'expressNumber' => 'Express Number',
            'goodsName' => 'Goods Name',
            'paymentAmt' => 'Payment Amt',
            'unpaidAmt' => 'Unpaid Amt',
        ];
    }
}
