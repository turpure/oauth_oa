<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
/**
 * This is the model class for collection "ebay_hot_rule".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property mixed $brand
 * @property mixed $cids
 * @property mixed $country
 * @property mixed $visitStart
 * @property mixed $visitEnd
 * @property mixed $popularStatus
 * @property mixed $salesThreeDayFlag
 * @property mixed $titleOrItemId
 * @property mixed $itemId
 * @property mixed $title
 * @property mixed $titleType
 * @property mixed $priceStart
 * @property mixed $priceEnd
 * @property mixed $soldStart
 * @property mixed $soldEnd
 * @property mixed $soldThePreviousDayStart
 * @property mixed $soldThePreviousDayEnd
 * @property mixed $paymentThePreviousDayStart
 * @property mixed $paymentThePreviousDayEnd
 * @property mixed $salesThreeDay1Start
 * @property mixed $salesThreeDay1End
 * @property mixed $salesThreeDayGrowthStart
 * @property mixed $salesThreeDayGrowthEnd
 * @property mixed $paymentThreeDay1Start
 * @property mixed $paymentThreeDay1End
 * @property mixed $salesWeek1Start
 * @property mixed $salesWeek1End
 * @property mixed $salesWeek2Start
 * @property mixed $salesWeek2End
 * @property mixed $salesWeekGrowthStart
 * @property mixed $salesWeekGrowthEnd
 * @property mixed $paymentWeek1Start
 * @property mixed $paymentWeek1End
 * @property mixed $marketplace
 * @property mixed $itemLocation
 * @property mixed $genTimeStart
 * @property mixed $genTimeEnd
 * @property mixed $sellerOrStore
 * @property mixed $storeLocation
 * @property mixed $soldThePreviousGrowthStart
 * @property mixed $soldThePreviousGrowthEnd
 * @property mixed $index
 * @property mixed $pageSize
 * @property mixed $orderColumn
 * @property mixed $sales_three_day1
 * @property mixed $sort
 * @property mixed $itemIdStatus
 * @property mixed $sellerOrStoreStatus
 * @property mixed $storeLocationStatus
 * @property mixed $itemLocationStatus
 * @property mixed $creator
 * @property mixed $createdDate
 * @property mixed $updatedDate
 */
class EbayHotRule extends \yii\mongodb\ActiveRecord
{

    public function behaviors()
    {
        return [[
            /**
             * TimestampBehavior：
             */
            'class' => TimestampBehavior::className(),
            'createdAtAttribute' => 'createdDate',
            'updatedAtAttribute' => 'updatedDate',
            'value' => date('Y-m-d H:i:s'),
        ],];
    }
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['product_engine', 'ebay_hot_rule'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'brand',
            'cids',
            'country',
            'visitStart',
            'visitEnd',
            'popularStatus',
            'salesThreeDayFlag',
            'titleOrItemId',
            'itemId',
            'title',
            'titleType',
            'priceStart',
            'priceEnd',
            'soldStart',
            'soldEnd',
            'soldThePreviousDayStart',
            'soldThePreviousDayEnd',
            'paymentThePreviousDayStart',
            'paymentThePreviousDayEnd',
            'salesThreeDay1Start',
            'salesThreeDay1End',
            'salesThreeDayGrowthStart',
            'salesThreeDayGrowthEnd',
            'paymentThreeDay1Start',
            'paymentThreeDay1End',
            'salesWeek1Start',
            'salesWeek1End',
            'salesWeek2Start',
            'salesWeek2End',
            'salesWeekGrowthStart',
            'salesWeekGrowthEnd',
            'paymentWeek1Start',
            'paymentWeek1End',
            'marketplace',
            'itemLocation',
            'genTimeStart',
            'genTimeEnd',
            'sellerOrStore',
            'storeLocation',
            'soldThePreviousGrowthStart',
            'soldThePreviousGrowthEnd',
            'index',
            'pageSize',
            'orderColumn',
            'sales_three_day1',
            'sort',
            'itemIdStatus',
            'sellerOrStoreStatus',
            'storeLocationStatus',
            'itemLocationStatus',
            'creator',
            'createdDate',
            'updatedDate',
            'ruleName',
            'ruleMark',

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['brand', 'cids', 'country', 'visitStart', 'visitEnd', 'popularStatus', 'salesThreeDayFlag', 'titleOrItemId', 'itemId', 'title', 'titleType', 'priceStart', 'priceEnd', 'soldStart', 'soldEnd', 'soldThePreviousDayStart', 'soldThePreviousDayEnd', 'paymentThePreviousDayStart', 'paymentThePreviousDayEnd', 'salesThreeDay1Start', 'salesThreeDay1End', 'salesThreeDayGrowthStart', 'salesThreeDayGrowthEnd', 'paymentThreeDay1Start', 'paymentThreeDay1End', 'salesWeek1Start', 'salesWeek1End', 'salesWeek2Start', 'salesWeek2End', 'salesWeekGrowthStart', 'salesWeekGrowthEnd', 'paymentWeek1Start', 'paymentWeek1End', 'marketplace', 'itemLocation', 'genTimeStart', 'genTimeEnd', 'sellerOrStore', 'storeLocation', 'soldThePreviousGrowthStart', 'soldThePreviousGrowthEnd', 'index', 'pageSize', 'orderColumn', 'sales_three_day1', 'sort', 'itemIdStatus', 'sellerOrStoreStatus', 'storeLocationStatus', 'itemLocationStatus','creator', 'createdDate', 'updatedDate','ruleName','ruleMark'], 'safe']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'brand' => 'Brand',
            'cids' => 'Cids',
            'country' => 'Country',
            'visitStart' => 'Visit Start',
            'visitEnd' => 'Visit End',
            'popularStatus' => 'Popular Status',
            'salesThreeDayFlag' => 'Sales Three Day Flag',
            'titleOrItemId' => 'Title Or Item ID',
            'itemId' => 'Item ID',
            'title' => 'Title',
            'titleType' => 'Title Type',
            'priceStart' => 'Price Start',
            'priceEnd' => 'Price End',
            'soldStart' => 'Sold Start',
            'soldEnd' => 'Sold End',
            'soldThePreviousDayStart' => 'Sold The Previous Day Start',
            'soldThePreviousDayEnd' => 'Sold The Previous Day End',
            'paymentThePreviousDayStart' => 'Payment The Previous Day Start',
            'paymentThePreviousDayEnd' => 'Payment The Previous Day End',
            'salesThreeDay1Start' => 'Sales Three Day1 Start',
            'salesThreeDay1End' => 'Sales Three Day1 End',
            'salesThreeDayGrowthStart' => 'Sales Three Day Growth Start',
            'salesThreeDayGrowthEnd' => 'Sales Three Day Growth End',
            'paymentThreeDay1Start' => 'Payment Three Day1 Start',
            'paymentThreeDay1End' => 'Payment Three Day1 End',
            'salesWeek1Start' => 'Sales Week1 Start',
            'salesWeek1End' => 'Sales Week1 End',
            'salesWeek2Start' => 'Sales Week2 Start',
            'salesWeek2End' => 'Sales Week2 End',
            'salesWeekGrowthStart' => 'Sales Week Growth Start',
            'salesWeekGrowthEnd' => 'Sales Week Growth End',
            'paymentWeek1Start' => 'Payment Week1 Start',
            'paymentWeek1End' => 'Payment Week1 End',
            'marketplace' => 'Marketplace',
            'itemLocation' => 'Item Location',
            'genTimeStart' => 'Gen Time Start',
            'genTimeEnd' => 'Gen Time End',
            'sellerOrStore' => 'Seller Or Store',
            'storeLocation' => 'Store Location',
            'soldThePreviousGrowthStart' => 'Sold The Previous Growth Start',
            'soldThePreviousGrowthEnd' => 'Sold The Previous Growth End',
            'index' => 'Index',
            'pageSize' => 'Page Size',
            'orderColumn' => 'Order Column',
            'sales_three_day1' => 'Sales Three Day1',
            'sort' => 'Sort',
            'itemIdStatus' => 'Item Id Status',
            'sellerOrStoreStatus' => 'Seller Or Store Status',
            'storeLocationStatus' => 'Store Location Status',
            'itemLocationStatus' => 'Item Location Status',
            'creator' => 'Creator',
            'createdDate' => 'Created Time',
            'updatedDate' => 'Updated Time',
            'ruleName' => 'Rule Name',
            'ruleMark' => 'Rule Mark',
        ];
    }
}
