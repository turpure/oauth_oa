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
 * @property int $marketplace
 * @property int $cate
 * @property int $subCate
 * @property int $createdDate
 * @property int $updatedDate
 */
class EbayCategory extends \yii\mongodb\ActiveRecord
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
        return ['product_engine', 'ebay_category'];
    }


    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'plat',
            'marketplace',
            'cate',
            'subCate',
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
            [['plat', 'marketplace', 'firstCate', 'secondCate', 'createdDate', 'updatedDate'], 'safe']
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
