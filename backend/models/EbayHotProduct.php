<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for collection "ebay_hot_product".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 */
class EbayHotProduct extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['product_engine', 'ebay_hot_product'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
        ];
    }
}
