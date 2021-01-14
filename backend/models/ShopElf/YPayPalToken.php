<?php

namespace backend\models\ShopElf;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "Y_PayPalToken".
 * @property int $id
 * @property string $accountName
 * @property string $username
 * @property string $signature
 * @property string $createdTime
 * @property int $isUsed
 * @property int $isUsedBalance
 * @property int $isUsedRefund
 * @property int $isUsedTransaction
 * @property string $mappingEbayName
 */
class YPayPalToken extends \yii\db\ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'Y_PayPalToken';
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
            'updatedAtAttribute' => false,
            'value' => date('Y-m-d H:i:s')
        ],];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['isUsed','isUsedBalance','isUsedRefund','isUsedTransaction'], 'integer'],
            [['accountName', 'username','signature','createdTime','mappingEbayName'], 'string'],
        ];
    }

}
