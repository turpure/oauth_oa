<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for collection "ebay_new_rule".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property mixed $cids
 * @property mixed $index
 * @property mixed $title
 * @property mixed $itemId
 * @property mixed $soldEnd
 * @property mixed $country
 * @property mixed $visitEnd
 * @property mixed $priceEnd
 * @property mixed $soldStart
 * @property mixed $titleType
 * @property mixed $sort
 * @property mixed $pageSize
 * @property mixed $priceStart
 * @property mixed $visitStart
 * @property mixed $marketplace
 * @property mixed $popularStatus
 * @property mixed $sellerOrStore
 * @property mixed $storeLocation
 * @property mixed $salesThreeDayFlag
 * @property mixed $orderColumn
 * @property mixed $listedTime
 * @property mixed $itemLocation
 * @property mixed $creator
 * @property mixed $createdDate
 * @property mixed $updatedDate
 * @property mixed $ruleName
 * @property mixed $ruleMark
 * @property mixed $publishedSite
 * @property mixed $site
 *
 */
class WishRule extends \yii\mongodb\ActiveRecord
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

    public function beforeSave($insert=true)
    {
        if(parent::beforeSave($insert)) {

            $defaultAttributes = [
                'cids' =>'', 'index' => 1, 'merchantStatus' => 1,'orderColumn' => 'view_rate1', 'sort' => 'DESC', 'pageSize' => 20
                ];
            $this->setAttributes($defaultAttributes);
        }
        return true;

    }

    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['product_engine', 'wish_rule'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'cids',
            'genTimeEnd',
            'genTimeStart',
            'hwc',
            'index',
            'intervalRatingEnd',
            'intervalRatingStart',
            'maxNumBoughtEnd',
            'maxNumBoughtStart',
            'merchant',
            'merchantStatus',
            'orderColumn',
            'pageSize',
            'pb',
            'pid',
            'pidStatus',
            'pname',
            'pnameStatus',
            'ratingEnd',
            'ratingStart',
            'sort',
            'totalpriceEnd',
            'totalpriceStart',
            'verified',
            'viewRate1End',
            'viewRate1Start',
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
            [['cids','genTimeEnd','genTimeStart','hwc','index','intervalRatingEnd','intervalRatingStart','maxNumBoughtEnd','maxNumBoughtStart',
                'merchant','merchantStatus','orderColumn','pageSize','pb','pid','pidStatus','pname','pnameStatus',
                'ratingEnd','ratingStart','sort','totalpriceEnd','totalpriceStart','verified','viewRate1End','viewRate1Start',
                'creator','createdDate','updatedDate','ruleName','ruleMark'], 'safe']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'cids' => 'Cids',
            'index' => 'Index',
            'genTimeEnd' => 'Gen Time End',
            'genTimeStart' => 'Gen Time Start',
            'hwc' => 'Hwc',
            'intervalRatingEnd' => 'Interval Rating End',
            'intervalRatingStart' => 'Interval Rating Start',
            'maxNumBoughtEnd' => 'Max Num Bought End',
            'maxNumBoughtStart' => 'Max Num Bought Start',
            'merchantStatus' => 'Merchant Status',
            'orderColumn' => 'Order Column',
            'sort' => 'Sort',
            'pageSize' => 'Page Size',
            'pb' => 'Pb',
            'pid' => 'Pid',
            'pidStatus' => 'Pid Status',
            'pname' => 'Pname',
            'pnameStatus' => 'Pname Status',
            'ratingEnd' => 'Rating End',
            'ratingStart' => 'Rating Start',
            'totalpriceEnd' => 'Total Price End',
            'totalpriceStart' => 'Total Price Start',
            'verified' => 'Verified',
            'viewRate1End' => 'View Rate1 End',
            'viewRate1Start' => 'View Rate1 Start',
            'creator' => 'Creator',
            'createdDate' => 'Created Time',
            'updatedDate' => 'Updated Time',
            'ruleName' => 'Rule Name',
            'ruleMark' => 'Rule Mark',
        ];
    }
}
