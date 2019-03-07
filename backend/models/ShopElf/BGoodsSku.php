<?php

namespace backend\models\ShopElf;

use Yii;

/**
 * This is the model class for table "B_goodsSku".
 *
 * @property int $NID
 * @property int $GoodsID
 * @property string $SKU
 * @property string $property1
 * @property string $property2
 * @property string $property3
 * @property string $SKUName
 * @property int $LocationID
 * @property string $BmpFileName
 * @property int $SellCount
 * @property string $Remark
 * @property int $SellCount1
 * @property int $SellCount2
 * @property int $SellCount3
 * @property double $Weight
 * @property string $CostPrice
 * @property string $RetailPrice
 * @property int $MaxNum
 * @property int $MinNum
 * @property string $GoodsSKUStatus
 * @property string $ChangeStatusTime
 * @property string $ASINN
 * @property string $UPC
 * @property string $ModelNum
 * @property string $ChangeCostTime
 * @property string $linkurl
 * @property string $CMinPrice
 */
class BGoodsSku extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'B_goodsSku';
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
            [['GoodsID'], 'required'],
            [['GoodsID', 'LocationID', 'SellCount', 'SellCount1', 'SellCount2', 'SellCount3', 'MaxNum', 'MinNum'], 'integer'],
            [['SKU', 'property1', 'property2', 'property3', 'SKUName', 'BmpFileName', 'Remark', 'GoodsSKUStatus', 'ASINN', 'UPC', 'ModelNum', 'linkurl'], 'string'],
            [['Weight', 'CostPrice', 'RetailPrice', 'CMinPrice'], 'number'],
            [['ChangeStatusTime', 'ChangeCostTime'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'NID' => 'Nid',
            'GoodsID' => 'Goods ID',
            'SKU' => 'Sku',
            'property1' => 'Property1',
            'property2' => 'Property2',
            'property3' => 'Property3',
            'SKUName' => 'Skuname',
            'LocationID' => 'Location ID',
            'BmpFileName' => 'Bmp File Name',
            'SellCount' => 'Sell Count',
            'Remark' => 'Remark',
            'SellCount1' => 'Sell Count1',
            'SellCount2' => 'Sell Count2',
            'SellCount3' => 'Sell Count3',
            'Weight' => 'Weight',
            'CostPrice' => 'Cost Price',
            'RetailPrice' => 'Retail Price',
            'MaxNum' => 'Max Num',
            'MinNum' => 'Min Num',
            'GoodsSKUStatus' => 'Goods Skustatus',
            'ChangeStatusTime' => 'Change Status Time',
            'ASINN' => 'Asinn',
            'UPC' => 'Upc',
            'ModelNum' => 'Model Num',
            'ChangeCostTime' => 'Change Cost Time',
            'linkurl' => 'Linkurl',
            'CMinPrice' => 'Cmin Price',
        ];
    }
}
