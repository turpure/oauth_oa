<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_shopifyGoodsSku".
 *
 * @property int $id
 * @property int $infoId
 * @property int $sid
 * @property string $sku
 * @property string $color
 * @property string $size
 * @property int $inventory
 * @property string $price
 * @property string $msrp
 * @property string $linkUrl
 * @property int $goodsSkuId
 * @property double $weight
 */
class OaShopifyGoodsSku extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_shopifyGoodsSku';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['infoId', 'sid', 'inventory', 'goodsSkuId'], 'integer'],
            [['price', 'msrp', 'weight'], 'number'],
            [['sku', 'color', 'size'], 'string', 'max' => 200],
            [['linkUrl'], 'string', 'max' => 500],
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
            'msrp' => 'Msrp',
            'linkUrl' => 'Link Url',
            'goodsSkuId' => 'Goods Sku Id',
            'weight' => 'Weight',
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
