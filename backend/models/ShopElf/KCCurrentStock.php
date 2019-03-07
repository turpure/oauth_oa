<?php

namespace backend\models\ShopElf;

use Yii;

/**
 * This is the model class for table "KC_CurrentStock".
 *
 * @property int $NID
 * @property int $StoreID
 * @property int $GoodsID
 * @property int $GoodsSKUID
 * @property string $Number
 * @property string $Money
 * @property string $Price
 * @property string $ReservationNum
 * @property string $OutCode
 * @property string $WarningCats
 * @property string $SaleDate
 * @property string $KcMaxNum
 * @property string $KcMinNum
 * @property int $SellCount1
 * @property int $SellCount2
 * @property int $SellCount3
 * @property int $SellDays
 * @property int $StockDays
 * @property int $SellCount
 */
class KCCurrentStock extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'KC_CurrentStock';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('py_db');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['StoreID', 'GoodsID'], 'required'],
            [['StoreID', 'GoodsID', 'GoodsSKUID', 'SellCount1', 'SellCount2', 'SellCount3', 'SellDays', 'StockDays', 'SellCount'], 'integer'],
            [['Number', 'Money', 'Price', 'ReservationNum', 'KcMaxNum', 'KcMinNum'], 'number'],
            [['OutCode', 'WarningCats'], 'string'],
            [['SaleDate'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'NID' => 'Nid',
            'StoreID' => 'Store ID',
            'GoodsID' => 'Goods ID',
            'GoodsSKUID' => 'Goods Skuid',
            'Number' => 'Number',
            'Money' => 'Money',
            'Price' => 'Price',
            'ReservationNum' => 'Reservation Num',
            'OutCode' => 'Out Code',
            'WarningCats' => 'Warning Cats',
            'SaleDate' => 'Sale Date',
            'KcMaxNum' => 'Kc Max Num',
            'KcMinNum' => 'Kc Min Num',
            'SellCount1' => 'Sell Count1',
            'SellCount2' => 'Sell Count2',
            'SellCount3' => 'Sell Count3',
            'SellDays' => 'Sell Days',
            'StockDays' => 'Stock Days',
            'SellCount' => 'Sell Count',
        ];
    }
}
