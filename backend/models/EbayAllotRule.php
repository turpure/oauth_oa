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
 * @property mixed $productNum
 * @property mixed $category
 * @property mixed $deliveryLocation
 * @property mixed $detail
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
            'productNum',
            'category',
            'deliveryLocation',
            'detail',
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
            [['username', 'depart', 'productNum', 'category', 'deliveryLocation', 'detail', 'createdDate', 'updatedDate'], 'safe']
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
            'productNum' => 'Product Num',
            'category' => 'Category',
            'deliveryLocation' => 'Delivery Location',
            'detail' => 'Detail',
            'createdDate' => 'Created Time',
            'updatedDate' => 'Updated Time',
        ];
    }
}
