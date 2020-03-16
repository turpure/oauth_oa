<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for collection "ebay_category".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property int $plat
 * @property int $countryId
 * @property int $country
 * @property int $cate
 * @property int $subCate
 */
class ShopeeCategory extends \yii\mongodb\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['product_engine', 'shopee_category'];
    }


    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'plat',
            'countryId',
            'country',
            'cate',
            'subCate',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['plat', 'countryId', 'country', 'cate', 'subCate',], 'safe']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'plat' => 'Plat',
            'marketplace' => 'Marketplace',
            'firstCate' => 'First Cate',
            'secondCate' => 'Second Cate',
            'createdDate' => 'Created Time',
            'updatedDate' => 'Updated Time',
        ];
    }




}
