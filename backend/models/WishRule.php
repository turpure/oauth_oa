<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for collection "wish_rule".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property mixed $cids
 * @property mixed $genTimeEnd
 * @property mixed $genTimeStart
 * @property mixed $hwc
 * @property mixed $index
 * @property mixed $intervalRatingEnd
 * @property mixed $intervalRatingStart
 * @property mixed $maxNumBoughtEnd
 * @property mixed $maxNumBoughtStart
 * @property mixed $merchant
 * @property mixed $merchantStatus
 * @property mixed $orderColumn
 * @property mixed $pageSize
 * @property mixed $pb
 * @property mixed $pid
 * @property mixed $pidStatus
 * @property mixed $pname
 * @property mixed $pnameStatus
 * @property mixed $ratingEnd
 * @property mixed $ratingStart
 * @property mixed $sort
 * @property mixed $totalpriceEnd
 * @property mixed $totalpriceStart
 * @property mixed $verified
 * @property mixed $viewRate1End
 * @property mixed $viewRate1Start
 * @property mixed $creator
 * @property mixed $createdDate
 * @property mixed $updatedDate
 * @property mixed $ruleName
 * @property mixed $ruleMark
 * @property mixed $ruleType
 * @property mixed $listedTime
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
                'cids' =>'', 'index' => 1, 'merchantStatus' => 1,'orderColumn' => 'max_num_bought', 'sort' => 'DESC', 'pageSize' => 20
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
            'ruleType',
            'listedTime'
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
                'creator','createdDate','updatedDate','ruleName','ruleMark','ruleType','listedTime'], 'safe']
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
            'ruleType' => 'Rule Type',
            'listedTime' => 'Listed Time',
        ];
    }
}
