<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
/**
 * This is the model class for collection "ebay_cate_rule".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property mixed $pyCate
 * @property int $detail
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
            [['pyCate', 'detail', 'createdDate', 'updatedDate'], 'safe']
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
            'detail' => 'Detail',
            'createdDate' => 'Created Time',
            'updatedDate' => 'Updated Time',
        ];
    }


}
