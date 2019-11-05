<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
/**
 * This is the model class for collection "ebay_hot_rule".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property mixed $username
 * @property mixed $depart
 * @property mixed $ruleType
 * @property mixed $ruleId
 * @property mixed $cateRuleId
 * @property mixed $productNum
 * @property mixed $category
 * @property mixed $deliveryLocation
 * @property mixed $createdDate
 * @property mixed $updatedDate
 */
class EbayAllotRule extends \yii\mongodb\ActiveRecord
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
        return ['product_engine', 'ebay_allot_rule'];
    }


    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'username',
            'depart',
            'ruleType',
            'ruleId',
            'cateRuleId',
            'productNum',
            'category',
            'deliveryLocation',
            'createdDate',
            'updatedDate',

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username', 'depart', 'ruleType', 'ruleId', 'cateRuleId', 'productNum', 'category', 'deliveryLocation', 'createdDate', 'updatedDate'], 'safe']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'username' => 'Username',
            'depart' => 'Depart',
            'ruleType' => 'Rule Type',
            'ruleId' => 'Rule Id',
            'cateRuleId' => 'Cate Rule Id',
            'productNum' => 'Product Num',
            'category' => 'Category',
            'deliveryLocation' => 'Delivery Location',
            'createdDate' => 'Created Time',
            'updatedDate' => 'Updated Time',
        ];
    }
}
