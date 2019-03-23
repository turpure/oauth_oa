<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_wishGoods".
 *
 * @property int $id
 * @property string $sku
 * @property string $title
 * @property string $description
 * @property int $inventory
 * @property string $price
 * @property string $msrp
 * @property string $shipping
 * @property string $shippingTime
 * @property string $tags
 * @property string $mainImage
 * @property int $goodsId
 * @property int $infoId
 * @property string $extraImages
 * @property string $headKeywords
 * @property string $requiredKeywords
 * @property string $randomKeywords
 * @property string $tailKeywords
 * @property string $wishTags
 * @property string $stockUp
 */
class OaWishGoods extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_wishGoods';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['description', 'extraImages','stockUp'], 'string'],
            [['inventory', 'goodsId', 'infoId',], 'integer'],
            [['price', 'msrp', 'shipping'], 'number'],
            [['sku'], 'string', 'max' => 50],
            [['title', 'mainImage'], 'string', 'max' => 2000],
            [['shippingTime', 'headKeywords', 'tailKeywords'], 'string', 'max' => 20],
            [['tags', 'wishTags'], 'string', 'max' => 500],
            [['requiredKeywords', 'randomKeywords'], 'string', 'max' => 300],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sku' => 'Sku',
            'title' => 'Title',
            'description' => 'Description',
            'inventory' => 'Inventory',
            'price' => 'Price',
            'msrp' => 'Msrp',
            'shipping' => 'Shipping',
            'shippingTime' => 'Shippingtime',
            'tags' => 'Tags',
            'mainImage' => 'Main Image',
            'goodsId' => 'Goodsid',
            'infoId' => 'Infoid',
            'extraImages' => 'Extra Images',
            'headKeywords' => 'Head Keywords',
            'requiredKeywords' => 'Required Keywords',
            'randomKeywords' => 'Random Keywords',
            'tailKeywords' => 'Tail Keywords',
            'wishTags' => 'Wishtags',
            'stockUp' => 'Stock Up',
        ];
    }
}
