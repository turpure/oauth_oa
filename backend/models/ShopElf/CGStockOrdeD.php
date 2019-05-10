<?php

namespace backend\models\ShopElf;

use Yii;

/**
 * This is the model class for table "CG_StockOrderD".
 *
 * @property int $NID
 * @property int $StockOrderNID
 * @property int $GoodsSKUID
 * @property int $GoodsID
 * @property string $Amount
 * @property string $Price
 * @property string $TaxRate
 * @property string $TaxPrice
 * @property string $TaxMoney
 * @property string $Money
 * @property string $AllMoney
 * @property string $Remark
 * @property string $InAmount
 * @property string $SupplierName
 * @property string $Telphone
 * @property string $StockAddress
 * @property string $MinPrice
 * @property string $BeforeAvgPrice
 * @property string $offerid
 * @property string $specId
 * @property string $supplierLoginId
 * @property string $bPrice
 * @property string $bAmount
 * @property string $UnStdQty
 */
class CGStockOrdeD extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'CG_StockOrderD';
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
            [['StockOrderNID', 'GoodsSKUID', 'GoodsID'], 'integer'],
            [['Amount', 'Price', 'TaxRate', 'TaxPrice', 'TaxMoney', 'Money', 'AllMoney', 'InAmount', 'MinPrice', 'BeforeAvgPrice', 'bPrice', 'bAmount', 'UnStdQty'], 'number'],
            [['Remark', 'SupplierName', 'Telphone', 'StockAddress', 'offerid', 'specId', 'supplierLoginId'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'NID' => 'Nid',
            'StockOrderNID' => 'Stock Order Nid',
            'GoodsSKUID' => 'Goods Skuid',
            'GoodsID' => 'Goods ID',
            'Amount' => 'Amount',
            'Price' => 'Price',
            'TaxRate' => 'Tax Rate',
            'TaxPrice' => 'Tax Price',
            'TaxMoney' => 'Tax Money',
            'Money' => 'Money',
            'AllMoney' => 'All Money',
            'Remark' => 'Remark',
            'InAmount' => 'In Amount',
            'SupplierName' => 'Supplier Name',
            'Telphone' => 'Telphone',
            'StockAddress' => 'Stock Address',
            'MinPrice' => 'Min Price',
            'BeforeAvgPrice' => 'Before Avg Price',
            'offerid' => 'Offerid',
            'specId' => 'Spec ID',
            'supplierLoginId' => 'Supplier Login ID',
            'bPrice' => 'B Price',
            'bAmount' => 'B Amount',
            'UnStdQty' => 'Un Std Qty',
        ];
    }
}
