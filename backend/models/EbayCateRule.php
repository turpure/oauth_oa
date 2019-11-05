<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
/**
 * This is the model class for collection "ebay_cate_rule".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property mixed $pyCate
 * @property int $plat
 * @property int $marketplace
 * @property int $cate
 * @property int $subCate
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
            [['pyCate', 'plat', 'marketplace', 'cate', 'subCate', 'createdDate', 'updatedDate'], 'safe']
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
            'cate' => 'Cate',
            'subCate' => 'Sub Cate',
            'createdDate' => 'Created Time',
            'updatedDate' => 'Updated Time',
        ];
    }


}
