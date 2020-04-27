<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_smtGoodsSku".
 *
 * @property int $id
 * @property int $infoId
 * @property int $sid
 * @property string $sku
 * @property string $color
 * @property string $size
 * @property int $quantity
 * @property string $price
 * @property string $shipping
 * @property string $msrp
 * @property string $shippingTime
 * @property string $pic_url
 * @property int $goodsSkuId
 * @property double $weight
 */
class OaSmtGoodsSku extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_smtGoodsSku';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['infoId', 'sid', 'quantity', 'goodsSkuId'], 'integer'],
            [['price', 'shipping', 'msrp', 'weight'], 'number'],
            [['sku', 'color', 'size'], 'string', 'max' => 200],
            [['shippingTime'], 'string', 'max' => 60],
            [['pic_url'], 'string', 'max' => 500],
            [['sku'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'infoId' => 'Info ID',
            'sid' => 'Sid',
            'sku' => 'Sku',
            'color' => 'Color',
            'size' => 'Size',
            'quantity' => 'Quantity',
            'price' => 'Price',
            'shipping' => 'Shipping',
            'msrp' => 'Msrp',
            'shippingTime' => 'Shipping Time',
            'pic_url' => 'Pic Url',
            'goodsSkuId' => 'Goods Sku ID',
            'weight' => 'Weight',
        ];
    }
}
