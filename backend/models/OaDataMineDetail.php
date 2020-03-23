<?php

namespace backend\models;

use Yii;
use backend\models\OaDataMine;

/**
 * This is the model class for table "proCenter.oa_dataMineDetail".
 *
 * @property int $id
 * @property int $mid
 * @property string $parentId
 * @property string $proName
 * @property string $description
 * @property string $tags
 * @property int $childId
 * @property string $color
 * @property double $proSize
 * @property string $quantity
 * @property string $price
 * @property string $msrPrice
 * @property string $shipping
 * @property string $shippingWeight
 * @property string $shippingTime
 * @property string $varMainImage
 * @property string $extraImage0
 * @property string $extraImage1
 * @property string $extraImage2
 * @property string $extraImage3
 * @property string $extraImage4
 * @property string $extraImage5
 * @property string $extraImage6
 * @property string $extraImage7
 * @property string $extraImage8
 * @property string $extraImage9
 * @property string $extraImage10
 * @property string $mainImage
 * @property string $pySku
 */
class OaDataMineDetail extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_dataMineDetail';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mid'], 'integer'],
            [['price', 'msrPrice', 'shippingWeight'], 'number'],
            [['parentId', 'proName', 'tags', 'childId','color', 'quantity', 'shipping', 'varMainImage', 'extraImage0', 'extraImage1', 'extraImage2', 'extraImage3', 'extraImage4', 'extraImage5', 'extraImage6', 'extraImage7', 'extraImage8', 'extraImage9', 'extraImage10'], 'string', 'max' => 355],
            [['mainImage'], 'string', 'max' => 500],
            [['description'], 'string'],
            [['shippingTime'], 'string', 'max' => 20],
            [['pySku','proSize'], 'string', 'max' => 40],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mid' => 'Mid',
            'parentId' => 'Parent ID',
            'proName' => 'Pro Name',
            'description' => 'Description',
            'tags' => 'Tags',
            'childId' => 'Child ID',
            'color' => 'Color',
            'proSize' => 'Pro Size',
            'quantity' => 'Quantity',
            'price' => 'Price',
            'msrPrice' => 'Msr Price',
            'shipping' => 'Shipping',
            'shippingWeight' => 'Shipping Weight',
            'shippingTime' => 'Shipping Time',
            'varMainImage' => 'Var Main Image',
            'extraImage0' => 'Extra Image0',
            'extraImage1' => 'Extra Image1',
            'extraImage2' => 'Extra Image2',
            'extraImage3' => 'Extra Image3',
            'extraImage4' => 'Extra Image4',
            'extraImage5' => 'Extra Image5',
            'extraImage6' => 'Extra Image6',
            'extraImage7' => 'Extra Image7',
            'extraImage8' => 'Extra Image8',
            'extraImage9' => 'Extra Image9',
            'extraImage10' => 'Extra Image10',
            'mainImage' => 'Main Image',
            'pySku' => 'Py Sku',
        ];
    }
    public function getOaDataMine()
    {
        return $this->hasOne(OaDataMine::className(),['id'=>'mid']);
    }
}
