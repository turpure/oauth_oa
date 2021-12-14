<?php

namespace backend\models;

use yii\db\ActiveRecord;

/**
 * ebay token "trade_send_access_token".
 *
 * @property string $id
 * @property string $account
 * @property string $token
 * @property integer $expire_date
 * @property integer $status
 * @property integer $type
 * @property string $refresh_token
 *
 */
class TradeSendAccessToken extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'trade_send_access_token';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account', 'token','refresh_token', 'expire_date', 'status','type'], 'required'],
        ];
    }
}