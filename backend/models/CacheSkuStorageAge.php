<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "cache_sku_storage_age2".
 *
 * @property int $id
 * @property string $goodsCode
 * @property string $skuName
 * @property string $salerName
 * @property string $img
 * @property string $storeName
 * @property string $goodsSkuStatus
 * @property int $totalNumber
 * @property string $totalMoney
 * @property int $number1
 * @property string $money1
 * @property int $number2
 * @property string $money2
 * @property string $maxStorageAge
 * @property string $lastPurchaseDate
 * @property string $createdTime
 */
class CacheSkuStorageAge extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cache_sku_storage_age2';
    }

    public function behaviors()
    {
        return [[
            /**
             * TimestampBehavior：
             */
            'class' => TimestampBehavior::className(),
            'createdAtAttribute' => 'createdTime',
            'updatedAtAttribute' => false,
            'value' => new Expression('NOW()'),
        ],];
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['createTime', 'lastPurchaseDate'], 'safe'],
            [['totalNumber', 'number1', 'number2'], 'integer'],
            [['totalMoney', 'money1', 'money2'], 'number'],
            [['goodsCode', 'skuName', 'salerName', 'img', 'storeName', 'goodsSkuStatus', 'maxStorageAge'], 'string', 'max' => 255],
        ];
    }






}
