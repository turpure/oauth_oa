<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "cache_express".
 *
 * @property int $id
 * @property string $suffix
 * @property int $tradeId
 * @property string $expressName
 * @property string $trackNo
 * @property string $orderTime
 * @property string $lastDate
 * @property string $lastDetail
 */
class CacheExpress extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cache_express';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tradeId'], 'integer'],
            [['orderTime', 'lastDate'], 'safe'],
            [['suffix', 'expressName'], 'string', 'max' => 60],
            [['trackNo', 'lastDetail'], 'string', 'max' => 50],
            [['tradeId', 'trackNo'], 'unique', 'targetAttribute' => ['tradeId', 'trackNo']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'suffix' => 'Suffix',
            'tradeId' => 'Trade ID',
            'expressName' => 'Express Name',
            'trackNo' => 'Track No',
            'orderTime' => 'Order Time',
            'lastDate' => 'Last Date',
            'lastDetail' => 'Last Detail',
        ];
    }
}
