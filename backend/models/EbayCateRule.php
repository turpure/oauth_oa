<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
/**
 * This is the model class for collection "ebay_hot_rule".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property mixed $pyCate
 * @property mixed $plat
 * @property mixed $ruleId
 * @property mixed $marketplace
 * @property mixed $firstCate
 * @property mixed $secondCate
 * @property mixed $createdDate
 * @property mixed $updatedDate
 */
class EbayCateRule extends \yii\mongodb\ActiveRecord
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
        return ['product_engine', 'ebay_cate_rule'];
    }


    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'pyCate',
            'plat',
            'marketplace',
            'firstCate',
            'secondCate',
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
            [['pyCate', 'plat', 'marketplace', 'firstCate', 'secondCate', 'createdDate', 'updatedDate'], 'safe']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'pyCate' => 'Py Cate',
            'plat' => 'Plat',
            'marketplace' => 'Marketplace',
            'firstCate' => 'First Cate',
            'secondCate' => 'Second Cate',
            'createdDate' => 'Created Time',
            'updatedDate' => 'Updated Time',
        ];
    }
}
