<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_supplierGoodsSku".
 *
 * @property int $id
 * @property int $supplierGoodsId
 * @property string $sku
 * @property string $property1
 * @property string $property2
 * @property string $property3
 * @property string $costPrice
 * @property string $purchasePrice
 * @property string $weight
 * @property string $image
 * @property string $lowestPrice
 * @property int $purchaseNumber
 * @property string $supplierGoodsSku
 */
class OaSupplierGoodsSku extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_supplierGoodsSku';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id'], 'required'],
            [['id', 'supplierGoodsId', 'purchaseNumber'], 'integer'],
            [['costPrice', 'purchasePrice', 'weight', 'lowestPrice'], 'number'],
            [['sku', 'property1', 'property2', 'property3', 'supplierGoodsSku'], 'string', 'max' => 50],
            [['image'], 'string', 'max' => 255],
            [['id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'supplierGoodsId' => 'Supplier Goods ID',
            'sku' => 'Sku',
            'property1' => 'Property1',
            'property2' => 'Property2',
            'property3' => 'Property3',
            'costPrice' => 'Cost Price',
            'purchasePrice' => 'Purchase Price',
            'weight' => 'Weight',
            'image' => 'Image',
            'lowestPrice' => 'Lowest Price',
            'purchaseNumber' => 'Purchase Number',
            'supplierGoodsSku' => 'Supplier Goods Sku',
        ];
    }
}
