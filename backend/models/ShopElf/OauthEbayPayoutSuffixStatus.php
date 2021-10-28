<?php

namespace backend\models\ShopElf;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "oauth_ebay_payout_suffix_status".
 *
 * @property int $id
 * @property string $suffix
 * @property int $isUsed
 * @property string $memo
 * @property string $createdTime
 * @property string $updatedTime
 */
class OauthEbayPayoutSuffixStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'oauth_ebay_payout_suffix_status';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('py_db');
    }

    public function behaviors()
    {
        return [[
            /**
             * TimestampBehavior：
             */
            'class' => TimestampBehavior::className(),
            'createdAtAttribute' => 'createdTime',
            'updatedAtAttribute' => 'updatedTime',
            'value' => date('Y-m-d H:i:s')
        ],];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['isUsed'], 'integer'],
            [['suffix', 'memo', 'createdTime', 'updatedTime'], 'string'],
        ];
    }

}
