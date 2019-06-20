<?php

namespace backend\models\ShopElf;

use Yii;

/**
 * This is the model class for table "oauth_joomUpdateExpressFare".
 *
 * @property int $tradeNid
 * @property string $salesTax
 * @property string $suffix
 * @property string $shippingAmt
 * @property string $orderTime
 * @property int $logicsWayNid
 * @property string $shipToCountryCode
 * @property string $expressName
 * @property string $sku
 * @property string $l_number
 */
class OauthJoomUpdateExpressFare extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'oauth_joomUpdateExpressFare';
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
            [['tradeNid', 'salesTax', 'shippingAmt'], 'required'],
            [['tradeNid', 'logicsWayNid'], 'integer'],
            [['salesTax', 'shippingAmt'], 'number'],
            [['suffix', 'shipToCountryCode', 'expressName', 'sku', 'l_number'], 'string'],
            [['orderTime'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'tradeNid' => 'Trade Nid',
            'salesTax' => 'Sales Tax',
            'suffix' => 'Suffix',
            'shippingAmt' => 'Shipping Amt',
            'orderTime' => 'Order Time',
            'logicsWayNid' => 'Logics Way Nid',
            'shipToCountryCode' => 'Ship To Country Code',
            'expressName' => 'Express Name',
            'sku' => 'Sku',
            'l_number' => 'L Number',
        ];
    }
}
