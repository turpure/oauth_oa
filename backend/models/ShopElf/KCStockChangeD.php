<?php

namespace backend\models\ShopElf;

use Yii;

/**
 * This is the model class for table "KC_StockChangeD".
 *
 * @property int $NID
 * @property int $StockChangeNID
 * @property int $GoodsSKUID
 * @property int $GoodsID
 * @property string $Amount
 * @property string $Price
 * @property string $Money
 * @property string $Remark
 * @property string $StockAmount
 * @property string $PackPersonFee
 * @property string $PackMaterialFee
 * @property string $HeadFreight
 * @property string $Tariff
 * @property string $inmoney
 * @property string $InPrice
 * @property int $InStockQty
 */
class KCStockChangeD extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'KC_StockChangeD';
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
            [['StockChangeNID', 'GoodsSKUID', 'GoodsID', 'InStockQty'], 'integer'],
            [['Amount', 'Price', 'Money', 'StockAmount', 'PackPersonFee', 'PackMaterialFee', 'HeadFreight', 'Tariff', 'inmoney', 'InPrice'], 'number'],
            [['Remark'], 'string'],
        ];
    }


}
