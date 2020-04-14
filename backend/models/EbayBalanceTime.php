<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "ebay_balance_time".
 *
 * @property int $id
 * @property string $account
 * @property string $balanceTime
 */
class EbayBalanceTime extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ebay_balance_time';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['account'], 'string', 'max' => 50],
            [['balanceTime'], 'string', 'max' => 10],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account' => 'Account',
            'balanceTime' => 'Balance Time',
        ];
    }
}
