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
 * @property string $tokenType
 * @property int $isUsed
 * @property int $usedBalance
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
            [['id','isUsed','usedBalance'], 'integer'],
            [['accountName', 'username','signature','tokenType','createdTime'], 'string'],
        ];
    }

}
