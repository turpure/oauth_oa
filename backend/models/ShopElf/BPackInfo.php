<?php

namespace backend\models\ShopElf;

use Yii;

/**
 * This is the model class for table "B_PackInfo".
 *
 * @property int $NID
 * @property string $PackCode
 * @property string $PackName
 * @property string $CostPrice
 * @property int $Used
 * @property string $Remark
 * @property int $Weight
 * @property string $BarCode
 */
class BPackInfo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'B_PackInfo';
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
            [['PackCode', 'PackName'], 'required'],
            [['PackCode', 'PackName', 'Remark', 'BarCode'], 'string'],
            [['CostPrice'], 'number'],
            [['Used', 'Weight'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'NID' => 'Nid',
            'PackCode' => 'Pack Code',
            'PackName' => 'Pack Name',
            'CostPrice' => 'Cost Price',
            'Used' => 'Used',
            'Remark' => 'Remark',
            'Weight' => 'Weight',
            'BarCode' => 'Bar Code',
        ];
    }
}
