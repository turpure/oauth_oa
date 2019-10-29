<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proEngine.ebay_shop_data".
 *
 * @property int $id
 * @property string $ItemID
 * @property string $StartTime 刊登开始时间
 * @property string $EndTime 刊登结束时间
 * @property string $Title
 * @property string $ViewItemURL
 * @property string $PayPalEmailAddress
 * @property string $CategoryID
 * @property string $CategoryName
 * @property string $CurrencyCode
 * @property string $Price
 * @property string $Quantity
 * @property string $QuantitySold
 * @property string $HitCount
 * @property string $SKU
 * @property string $GalleryURL
 * @property string $PictureURL
 * @property string $StoreName
 * @property string $Site 站点国家
 * @property string $Location
 * @property string $CreateTime 数据创建时间
 * @property string $UpdateTime
 */
class EbayShopData extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proEngine.ebay_shop_data';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['Price'], 'number'],
            [['PictureURL'], 'string'],
            [['CreateTime', 'UpdateTime'], 'safe'],
            [['ItemID'], 'string', 'max' => 100],
            [['StartTime', 'EndTime', 'CategoryID', 'CurrencyCode', 'Quantity', 'QuantitySold', 'HitCount', 'StoreName', 'Site'], 'string', 'max' => 50],
            [['Title', 'ViewItemURL', 'PayPalEmailAddress', 'CategoryName', 'SKU', 'GalleryURL', 'Location'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ItemID' => 'Item ID',
            'StartTime' => 'Start Time',
            'EndTime' => 'End Time',
            'Title' => 'Title',
            'ViewItemURL' => 'View Item Url',
            'PayPalEmailAddress' => 'Pay Pal Email Address',
            'CategoryID' => 'Category ID',
            'CategoryName' => 'Category Name',
            'CurrencyCode' => 'Currency Code',
            'Price' => 'Price',
            'Quantity' => 'Quantity',
            'QuantitySold' => 'Quantity Sold',
            'HitCount' => 'Hit Count',
            'SKU' => 'Sku',
            'GalleryURL' => 'Gallery Url',
            'PictureURL' => 'Picture Url',
            'StoreName' => 'Store Name',
            'Site' => 'Site',
            'Location' => 'Location',
            'CreateTime' => 'Create Time',
            'UpdateTime' => 'Update Time',
        ];
    }
}
