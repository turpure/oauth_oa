<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proEngine.ebay_products".
 *
 * @property int $id
 * @property string $item_id
 * @property string $main_image
 * @property string $title
 * @property string $cids
 * @property double $price
 * @property int $sold
 * @property int $sold_the_previous_day
 * @property double $payment_the_previous_day
 * @property int $sold_the_previous_growth
 * @property int $sales_week1
 * @property int $sales_week2
 * @property int $sales_week_growth
 * @property double $payment_week1
 * @property double $payment_week2
 * @property string $item_location
 * @property int $watchers
 * @property string $last_modi_time
 * @property string $stat_time
 * @property string $gen_time
 * @property string $seller
 * @property string $store
 * @property string $store_location
 * @property string $category_structure
 * @property int $sales_three_day1
 * @property int $sales_three_day2
 * @property int $sales_three_day_growth
 * @property double $payment_three_day1
 * @property double $payment_three_day2
 * @property int $visit
 * @property int $sales_three_day_flag
 * @property string $item_url
 * @property string $marketplace
 * @property string $popular
 * @property string $station
 */
class EbayProducts extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proEngine.ebay_products';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['price', 'payment_the_previous_day', 'payment_week1', 'payment_week2', 'payment_three_day1', 'payment_three_day2'], 'number'],
            [['sold', 'sold_the_previous_day', 'sold_the_previous_growth', 'sales_week1', 'sales_week2', 'sales_week_growth', 'watchers', 'sales_three_day1', 'sales_three_day2', 'sales_three_day_growth', 'visit', 'sales_three_day_flag'], 'integer'],
            [['last_modi_time', 'stat_time', 'gen_time'], 'safe'],
            [['item_id', 'marketplace'], 'string', 'max' => 20],
            [['main_image', 'title', 'item_url'], 'string', 'max' => 300],
            [['cids', 'popular'], 'string', 'max' => 100],
            [['item_location', 'seller', 'store', 'store_location'], 'string', 'max' => 50],
            [['category_structure'], 'string', 'max' => 500],
            [['station'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'item_id' => 'Item ID',
            'main_image' => 'Main Image',
            'title' => 'Title',
            'cids' => 'Cids',
            'price' => 'Price',
            'sold' => 'Sold',
            'sold_the_previous_day' => 'Sold The Previous Day',
            'payment_the_previous_day' => 'Payment The Previous Day',
            'sold_the_previous_growth' => 'Sold The Previous Growth',
            'sales_week1' => 'Sales Week1',
            'sales_week2' => 'Sales Week2',
            'sales_week_growth' => 'Sales Week Growth',
            'payment_week1' => 'Payment Week1',
            'payment_week2' => 'Payment Week2',
            'item_location' => 'Item Location',
            'watchers' => 'Watchers',
            'last_modi_time' => 'Last Modi Time',
            'stat_time' => 'Stat Time',
            'gen_time' => 'Gen Time',
            'seller' => 'Seller',
            'store' => 'Store',
            'store_location' => 'Store Location',
            'category_structure' => 'Category Structure',
            'sales_three_day1' => 'Sales Three Day1',
            'sales_three_day2' => 'Sales Three Day2',
            'sales_three_day_growth' => 'Sales Three Day Growth',
            'payment_three_day1' => 'Payment Three Day1',
            'payment_three_day2' => 'Payment Three Day2',
            'visit' => 'Visit',
            'sales_three_day_flag' => 'Sales Three Day Flag',
            'item_url' => 'Item Url',
            'marketplace' => 'Marketplace',
            'popular' => 'Popular',
            'station' => 'Station',
        ];
    }
}
