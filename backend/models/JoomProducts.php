<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proEngine.joom_products".
 *
 * @property int $id
 * @property string $productId
 * @property string $cateId
 * @property string $productName
 * @property double $price
 * @property double $msrPrice
 * @property string $mainImage
 * @property double $rating
 * @property string $publishedDate
 * @property double $rate_week1
 * @property double $rate_week2
 * @property double $interval_rating
 * @property double $hot_index
 * @property int $hot_flag
 * @property string $lastModifyTime
 * @property int $reviewsCount
 * @property string $storeId
 */
class JoomProducts extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proEngine.joom_products';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['price', 'msrPrice', 'rating', 'rate_week1', 'rate_week2', 'interval_rating', 'hot_index'], 'number'],
            [['publishedDate', 'lastModifyTime'], 'safe'],
            [['hot_flag', 'reviewsCount'], 'integer'],
            [['productId', 'cateId'], 'string', 'max' => 100],
            [['productName', 'storeId'], 'string', 'max' => 200],
            [['mainImage'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'productId' => 'Product ID',
            'cateId' => 'Cate ID',
            'productName' => 'Product Name',
            'price' => 'Price',
            'msrPrice' => 'Msr Price',
            'mainImage' => 'Main Image',
            'rating' => 'Rating',
            'publishedDate' => 'Published Date',
            'rate_week1' => 'Rate Week1',
            'rate_week2' => 'Rate Week2',
            'interval_rating' => 'Interval Rating',
            'hot_index' => 'Hot Index',
            'hot_flag' => 'Hot Flag',
            'lastModifyTime' => 'Last Modify Time',
            'reviewsCount' => 'Reviews Count',
            'storeId' => 'Store ID',
        ];
    }
}
