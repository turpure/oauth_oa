<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_supplierOrderDetail".
 *
 * @property int $id
 * @property int $orderId
 * @property string $sku
 * @property string $image
 * @property string $supplierGoodsSku
 * @property string $goodsName
 * @property string $property1
 * @property string $property2
 * @property string $property3
 * @property int $purchaseNumber
 * @property string $purchasePrice
 * @property int $deliveryAmt
 * @property string $goodsCode
 * @property int $deliveryNumber
 * @property string $deliveryStatus
 * @property string $paymentStatus
 * @property string $deliveryTime
 */
class OaSupplierOrderDetail extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_supplierOrderDetail';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['orderId', 'purchaseNumber', 'deliveryAmt', 'deliveryNumber'], 'integer'],
            [['purchasePrice'], 'number'],
            [['deliveryTime'], 'safe'],
            [['sku', 'image', 'supplierGoodsSku', 'goodsName', 'property1', 'property2', 'property3', 'goodsCode', 'deliveryStatus', 'paymentStatus'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'orderId' => 'Order ID',
            'sku' => 'Sku',
            'image' => 'Image',
            'supplierGoodsSku' => 'Supplier Goods Sku',
            'goodsName' => 'Goods Name',
            'property1' => 'Property1',
            'property2' => 'Property2',
            'property3' => 'Property3',
            'purchaseNumber' => 'Purchase Number',
            'purchasePrice' => 'Purchase Price',
            'deliveryAmt' => 'Delivery Amt',
            'goodsCode' => 'Goods Code',
            'deliveryNumber' => 'Delivery Number',
            'deliveryStatus' => 'Delivery Status',
            'paymentStatus' => 'Payment Status',
            'deliveryTime' => 'Delivery Time',
        ];
    }

    /**
     * @brief join with oa_supplierOrder
     */
    public function getOaSupplierOrder() {
        return $this->hasOne(OaSupplierOrder::className(),['id'=>'orderId']);
    }


}
