<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_ebayGoodsSku".
 *
 * @property int $id
 * @property int $itemId
 * @property int $sid
 * @property int $infoId
 * @property string $sku
 * @property int $quantity
 * @property string $retailPrice
 * @property string $imageUrl
 * @property string $property
 */
class OaEbayGoodsSku extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_ebayGoodsSku';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['itemId', 'sid', 'infoId', 'quantity'], 'integer'],
            [['retailPrice'], 'number'],
            [['imageUrl', 'property'], 'string'],
            [['sku'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'itemId' => 'Item ID',
            'sid' => 'Sid',
            'infoId' => 'Info ID',
            'sku' => 'Sku',
            'quantity' => 'Quantity',
            'retailPrice' => 'Retail Price',
            'imageUrl' => 'Image Url',
            'property' => 'Property',
        ];
    }
}
