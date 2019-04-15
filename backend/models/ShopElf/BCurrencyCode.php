<?php

namespace backend\models\ShopElf;

use Yii;

/**
 * This is the model class for table "B_CurrencyCode".
 *
 * @property int $NID
 * @property string $CURRENCYCODE
 * @property string $CurrencyName
 * @property string $ExchangeRate
 * @property int $Used
 * @property string $Remark
 */
class BCurrencyCode extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'B_CurrencyCode';
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
            [['CURRENCYCODE', 'CurrencyName', 'Remark'], 'string'],
            [['ExchangeRate'], 'number'],
            [['Used'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'NID' => 'Nid',
            'CURRENCYCODE' => 'Currencycode',
            'CurrencyName' => 'Currency Name',
            'ExchangeRate' => 'Exchange Rate',
            'Used' => 'Used',
            'Remark' => 'Remark',
        ];
    }
}
