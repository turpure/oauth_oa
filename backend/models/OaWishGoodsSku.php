<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_wishGoodsSku".
 *
 * @property int $id
 * @property int $infoId
 * @property int $sid
 * @property string $sku
 * @property string $color
 * @property string $size
 * @property int $inventory
 * @property string $price
 * @property string $shipping
 * @property string $msrp
 * @property string $shippingTime
 * @property string $linkUrl
 * @property string $wishLinkUrl
 * @property int $goodsSkuId
 * @property double $weight
 * @property string $joomPrice
 * @property string $joomShipping
 */
class OaWishGoodsSku extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_wishGoodsSku';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['infoId', 'sid', 'inventory', 'goodsSkuId'], 'integer'],
            [['price', 'shipping', 'msrp', 'weight', 'joomPrice', 'joomShipping', 'fyndiqPrice', 'fyndiqMsrp'], 'number'],
            [['sku', 'color', 'size'], 'string', 'max' => 200],
            [['shippingTime'], 'string', 'max' => 60],
            [['linkUrl', 'wishLinkUrl'], 'string', 'max' => 500],
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
            'inventory' => 'Inventory',
            'price' => 'Price',
            'shipping' => 'Shipping',
            'msrp' => 'Msrp',
            'shippingTime' => 'Shipping Time',
            'linkUrl' => 'Link Url',
            'wishLinkUrl' => 'Wish Link Url',
            'goodsSkuId' => 'Goods Sku Id',
            'weight' => 'Weight',
            'joomPrice' => 'Joom Price',
            'joomShipping' => 'Joom Shipping',
        ];
    }

    /**
     * @brief linked with oaWishGoods
     */
    public function getOaWishGoods()
    {
        return $this->hasOne(OaWishGoods::className(),['infoId'=>'infoId']);
    }

}
