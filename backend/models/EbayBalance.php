<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "ebay_balance".
 *
 * @property int $id
 * @property string $accountName
 * @property double $balance
 * @property string $currency
 * @property string $updatedDate
 */
class EbayBalance extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ebay_balance';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['balance'], 'number'],
            [['updatedDate'], 'safe'],
            [['accountName'], 'string', 'max' => 50],
            [['currency'], 'string', 'max' => 10],
            [['accountName', 'updatedDate'], 'unique', 'targetAttribute' => ['accountName', 'updatedDate']],
            [['accountName', 'currency', 'updatedDate'], 'unique', 'targetAttribute' => ['accountName', 'currency', 'updatedDate']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'accountName' => 'Account Name',
            'balance' => 'Balance',
            'currency' => 'Currency',
            'updatedDate' => 'Updated Date',
        ];
    }
}
