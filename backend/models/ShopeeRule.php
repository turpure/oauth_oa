<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for collection "wish_rule".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property mixed $cids
 * @property mixed $country
 * @property mixed $genTimeEnd
 * @property mixed $genTimeStart
 * @property mixed $index
 * @property mixed $isShopeeVerified
 * @property mixed $likedCountEnd
 * @property mixed $likedCountStart
 * @property mixed $merchant
 * @property mixed $merchantStatus
 * @property mixed $orderColumn
 * @property mixed $pageSize
 * @property mixed $paymentEnd
 * @property mixed $paymentStart
 * @property mixed $paymentThreeDay1End
 * @property mixed $paymentThreeDay1Start
 * @property mixed $pid
 * @property mixed $pidOrTitle
 * @property mixed $pidStatus
 * @property mixed $priceEnd
 * @property mixed $priceStart
 * @property mixed $ratingCountEnd
 * @property mixed $ratingCountStart
 * @property mixed $ratingEnd
 * @property mixed $ratingStart
 * @property mixed $sort
 * @property mixed $salesThreeDay1End
 * @property mixed $salesThreeDay1Start
 * @property mixed $salesThreeDayGrowthEnd
 * @property mixed $salesThreeDayGrowthStart
 * @property mixed $shopLocation
 * @property mixed $shopLocationStatus
 * @property mixed $soldEnd
 * @property mixed $soldStart
 * @property mixed $title
 * @property mixed $titleStatus
 * @property mixed $creator
 * @property mixed $createdDate
 * @property mixed $updatedDate
 * @property mixed $ruleName
 * @property mixed $ruleMark
 * @property mixed $ruleType
 * @property mixed $listedTime
 *
 */
class ShopeeRule extends \yii\mongodb\ActiveRecord
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
                'cids' =>[], 'index' => 1, 'merchantStatus' => 1, 'sort' => 'DESC', 'pageSize' => 20,
                'titleStatus' => 1, 'pidStatus' => 1, 'isUsed' => 1
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
        return ['product_engine', 'shopee_rule'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'cids',
            'country',
            'genTimeEnd',
            'genTimeStart',
            'historicalSoldEnd',
            'historicalSoldStart',
            'index',
            'isShopeeVerified',
            'likedCountEnd',
            'likedCountStart',
            'merchant',
            'merchantStatus',
            'orderColumn',
            'pageSize',
            'paymentEnd',
            'paymentStart',
            'paymentThreeDay1End',
            'paymentThreeDay1Start',
            'pid',
            'pidOrTitle',
            'pidStatus',
            'priceEnd',
            'priceStart',
            'ratingEnd',
            'ratingStart',
            'ratingCountEnd',
            'ratingCountStart',
            'salesThreeDay1End',
            'salesThreeDay1Start',
            'salesThreeDayGrowthEnd',
            'salesThreeDayGrowthStart',
            'shopLocation',
            'shopLocationStatus',
            'soldEnd',
            'soldStart',
            'sort',
            'title',
            'titleStatus',
            'creator',
            'createdDate',
            'updatedDate',
            'ruleName',
            'ruleMark',
            'ruleType',
            'listedTime',
            'type',
            'isUsed'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cids', 'country', 'genTimeEnd', 'genTimeStart', 'historicalSoldEnd', 'historicalSoldStart', 'index',
                'isShopeeVerified', 'likedCountEnd', 'likedCountStart', 'merchant', 'merchantStatus', 'orderColumn', 'pageSize',
                'paymentEnd', 'paymentStart', 'paymentThreeDay1End', 'paymentThreeDay1Start',
                'pid', 'pidOrTitle', 'pidStatus', 'priceEnd', 'priceStart', 'ratingEnd', 'ratingStart', 'ratingCountEnd', 'ratingCountStart',
                'salesThreeDay1End', 'salesThreeDay1Start', 'salesThreeDayGrowthEnd', 'salesThreeDayGrowthStart',
                'shopLocation', 'shopLocationStatus', 'soldEnd', 'soldStart', 'sort', 'title', 'titleStatus',
                'creator','createdDate','updatedDate','ruleName','ruleMark','ruleType','listedTime','type','isUsed'], 'safe']
        ];
    }

}
